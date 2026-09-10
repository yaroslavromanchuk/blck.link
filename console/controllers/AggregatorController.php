<?php

namespace console\controllers;

use backend\models\Aggregator;
use backend\models\Artist;
use backend\models\InvoiceItems;
use backend\models\Percentage;
use common\models\t;
use Yii;
use yii\console\Controller;
use yii\db\Query;

/**
 * /15 * * * * php yii aggregator/sync-track-map
 */
class AggregatorController extends Controller
{
    public function actionSyncTrackMap()
    {
        $res = Yii::$app->db->createCommand("
            INSERT IGNORE INTO track_to_aggregator (aggregator_id, track_id)
            SELECT DISTINCT
                ar.aggregator_id,
                ari.track_id
            FROM aggregator_report_item ari
            JOIN aggregator_report ar
                ON ar.id = ari.report_id
        ")->execute();
        
       // t::log(sprintf("Added %d mapping", $res));

        if ($res > 0) {
            $this->stdout(sprintf("Added %d mapping\n", $res));
        }
        
        // $this->stdout("Aggregator-track mapping synced\n");
    }
    
    public function migrate()
    {
        $db = Yii::$app->db;
        
        $reports = (new Query())
            ->from('aggregator_report')
            ->all();
        
        foreach ($reports as $report) {
            
            $incomeInvoice = (new Query())
                ->from('invoice')
                ->where([
                    'aggregator_report_id' => $report['id'],
                    'invoice_type' => 1
                ])
                ->one();
            
            if (!$incomeInvoice) {
                continue;
            }
            
            $reportItems = (new Query())
                ->from('aggregator_report_item')
                ->where(['report_id' => $report['id']])
                ->all();
            
            foreach ($reportItems as $ari) {
                
                // всі старі invoice_items по треку
                $oldItems = (new Query())
                    ->from('invoice_items_backup_2026_03_30')
                    ->where([
                        'invoice_id' => $incomeInvoice['invoice_id'],
                        'track_id'   => $ari['track_id']
                    ])
                    ->all();
                
                foreach ($oldItems as $old) {
                    
                    /*
                     * old.artist_percentage — частка артиста у треку
                     * old.percentage        — частка артиста від своєї долі (контракт)
                     */
                    
                    $artistGross = $ari['amount']
                        * $old['artist_percentage'] / 100;
                    
                    // артист або лейбл
                    if ((int)$old['artist_id'] !== 0) {
                        // артист
                        $finalAmount = $artistGross
                            * $old['percentage'] / 100;
                    } else {
                        // лейбл
                        $finalAmount = $artistGross
                            * (100 - $old['percentage']) / 100;
                    }
                    
                    $finalAmount = round($finalAmount, 8);
                    
                    $db->createCommand()->insert('invoice_items_temp', [
                        'invoice_id' => $incomeInvoice['invoice_id'],
                        'track_id'   => $ari['track_id'],
                        'artist_id'  => $old['artist_id'],
                        'artist_percentage' => $old['artist_percentage'],
                        'percentage' => $old['percentage'],
                        'amount'     => $finalAmount,
                        'date_item'  => $old['date_item'],
                        'source_report_item_id' => $ari['id'],
                    ])->execute();
                }
            }
        }
    }
    
    public function actionFix() {
        
        $items = InvoiceItems::find()
            ->joinWith('invoice')
            ->where([
                'invoice.invoice_type' => 1,
            ])
            ->andWhere(['>', 'invoice_items.amount', 0])
            ->andWhere([
                'OR',
                ['invoice_items.artist_percentage' => null],
                ['invoice_items.artist_percentage' => 0],
                ['invoice_items.percentage' => null],
                ['invoice_items.percentage' => 0],
            ])
            ->all();
        
        foreach ($items as $item) {
            
            $invoice = $item->invoice;
            $trackId = $item->track_id;
            $artistId = $item->artist_id;
            
            // LABEL
            if ((int)$artistId === Artist::LABEL) {
             //   continue;
                
                // беремо артиста-джерело
                $fromArtistId = $item->from_artist_id;
                
                if (!$fromArtistId) {
                    continue;
                }
                
                // беремо artist percentage того артиста
                $artistItem = InvoiceItems::find()
                    ->where([
                        'invoice_id' => $invoice->invoice_id,
                        'track_id' => $trackId,
                        'artist_id' => $fromArtistId,
                    ])
                    ->one();
                
               /* if (!$artistItem) {
                    $artistItem = InvoiceItems::find()
                        ->where([
                            //'invoice_id' => $invoice->invoice_id,
                            'track_id' => $trackId,
                            'artist_id' => 0,
                            'from_artist_id' => $fromArtistId,
                        ])
                        ->andWhere(['>', 'invoice_items.amount', 0])
                        ->andWhere(['>', 'invoice_items.percentage', 0])
                        ->one();
                }
                
                if (!$artistItem) {
                    $artistItem = InvoiceItems::find()
                        ->where([
                            //'invoice_id' => $invoice->invoice_id,
                            'track_id' => $trackId,
                            'artist_id' => $fromArtistId,
                        ])
                        ->andWhere(['>', 'invoice_items.amount', 0])
                        ->andWhere(['>', 'invoice_items.percentage', 0])
                        ->one();
                    
                }*/
                
                if ($artistItem && $artistItem->percentage) {
                    $item->percentage = 100 - $artistItem->percentage;
                    $item->artist_percentage = $artistItem->artist_percentage;
                    $item->save(false);
                    continue;
                }
                
                $artistItem = InvoiceItems::find()
                    ->where([
                        //'invoice_id' => $invoice->invoice_id,
                        'track_id' => $trackId,
                        'artist_id' => 0,
                        'from_artist_id' => $fromArtistId,
                    ])
                    ->andWhere(['>', 'invoice_items.amount', 0])
                    ->andWhere(['>', 'invoice_items.percentage', 0])
                    ->one();
                
                if ($artistItem && $artistItem->percentage) {
                    $item->percentage = $artistItem->percentage;
                    $item->artist_percentage = $artistItem->artist_percentage;
                    $item->save(false);
                    continue;
                }
                
                
                continue;
            }
            
            // ARTIST
            // визначаємо artist_percentage (ownership split)
            
            $artist = Artist::findOne($artistId);
            
            if ($artist->isClient()) {
                $percentageArtist = $artist->percentage; // публішинг
                
                $aggregator = Aggregator::findOne($invoice->aggregator_id);
                
                if ($aggregator->service_type_id == 1) { // дистрибуція
                    $percentageArtist = $artist->percentage_distribution;
                }
                
                if (empty($item->artist_percentage)) {
                    $item->artist_percentage = 100;
                }
                
                if (empty($item->percentage)) {
                    $item->percentage = $percentageArtist;
                    
                }
                
                $item->save(false);
                
                continue;
            }
            
            if (empty($item->artist_percentage)) {
                
                $data = (new Query())
                    ->select([
                        'track_to_percentage.artist_id',
                        '100 / COUNT(aggregator_to_ownership_type.id)
             * SUM(track_to_percentage.percentage) / 100 AS percentage'
                    ])
                    ->from('track_to_percentage')
                    ->leftJoin(
                        'aggregator_to_ownership_type',
                        'aggregator_to_ownership_type.ownership_type_id = track_to_percentage.ownership_type'
                    )
                    ->where([
                        'track_to_percentage.track_id' => $trackId,
                        'aggregator_to_ownership_type.aggregator_id' => $invoice->aggregator_id,
                        'track_to_percentage.artist_id' => $artistId,
                    ])
                    ->groupBy(['track_to_percentage.artist_id'])
                    ->one();
                
                if (!$data || $data['percentage'] <= 0) {
                    continue;
                }
                
                $artistPercentage = (int)$data['percentage'];
                $item->artist_percentage = $artistPercentage;
            }
            
            if (empty($item->percentage)) {
                // artist → label split
                $labelPercentageRow = Percentage::findOne([
                    'track_id' => $trackId,
                    'artist_id' => $artistId,
                    'ownership_type' => 5,
                ]);
                
                $contractPercentage = $labelPercentageRow
                    ? (int)$labelPercentageRow->percentage
                    : 0;
                
                
                $item->percentage = $contractPercentage;
            }
            
            $item->save(false);
        }
        
    }
}