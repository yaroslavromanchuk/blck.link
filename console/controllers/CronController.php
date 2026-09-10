<?php

namespace console\controllers;

use backend\controllers\InvoiceItemsController;
use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\SubLabel;
use backend\models\Track;
use common\models\User;
use Exception;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

class CronController extends Controller
{
    public function actionReport($id) {
        echo $id . "\n";
        $c = new InvoiceItemsController('invoice-items', Yii::$app);
        try {
            $c->actionExportAct($id, false);
        } catch (Throwable $e) {
            echo $e->getMessage();
            exit(1);
        }

        return ExitCode::OK;
    }
    /**
     * List cron.
     * This is used for shell completion.
     * @since 2.0.11
     */
    public function actionRun(int $id = null)
    {
        echo $id . "\n";
// Логіка, яку треба виконати
        //Yii::info('Cron запущено: ' . date('Y-m-d H:i:s'), 'cron');
       // Yii::error('Cron запущено: ' . date('Y-m-d H:i:s'), 'cron');
        echo "Cron виконано успішно\n";
       // echo User::findOne(1)->username;
        //echo '+++++';
        return ExitCode::OK;
    }
    
    public function actionCal(int $invoiceId = null)
    {
        if (is_null($invoiceId)) {
            echo "Потрібно вказати інвойс для прорахунку\n";
            return ExitCode::OK;
        }
        
        echo "Invoice ID: " . $invoiceId . "\n";
        
        $invoice = Invoice::findOne($invoiceId);
        
        if (!$invoice) {
            echo "Не вдалсоь знайти інвойс\n";
            return ExitCode::OK;
        }
        
        if ($invoice->invoice_type != InvoiceType::$debit) {
            echo "Бонуси нараховуються тільки при нарахувані звітів\n";
            return ExitCode::OK;
        }
        
        if ($invoice->invoice_status_id != InvoiceStatus::Calculated) {
            echo "Бонуси нараховуються тільки на проведені звіти\n";
            return ExitCode::OK;
        }
        
        $invoice->calculateUser();
        
        return ExitCode::OK;
    }
    public function actionMigrate(?int $labelId = null) {
        
        if (is_null($labelId)) {
            exit('Empty labelId');
        }
        
        $label = SubLabel::findOne($labelId);
        
        if (!$label) {
            exit('Error label');
        }
        
        if (empty($label->migrated)) {
            exit('Error migrated');
        }
        
        try {
            $sql1 = "UPDATE `invoice_items` SET `artist_id`= {$label->migrated} WHERE `artist_id` in (SELECT `id` FROM `artist` WHERE `id` != {$label->migrated} AND `label_id` = {$label->id})";
            Yii::$app->db->createCommand($sql1)->execute();
            $sql2 = "UPDATE `invoice_items` SET `from_artist_id`= {$label->migrated} WHERE `from_artist_id` in (SELECT `id` FROM `artist` WHERE `id` != {$label->migrated} AND `label_id` = {$label->id})";
            Yii::$app->db->createCommand($sql2)->execute();
            $sql3 = "UPDATE `track` SET `artist_id`= {$label->migrated} WHERE `artist_id` in (SELECT `id` FROM `artist` WHERE `id` != {$label->migrated} AND `label_id` = {$label->id})";
            Yii::$app->db->createCommand($sql3)->execute();
        } catch (\Throwable $e) {
            exit($e->getMessage());
        }
        
        exit('Ok') . PHP_EOL;
        
        /*
        
        if ($label->migrated) {
            exit('Цей лейбл вже змігровано. Артіст ID' . $label->migrated);
        }
        
        $artist = new Artist();
        $artist->type_id = 2;
        $artist->artist_type_id = $label->label_type_id;
        $artist->records = 0;
        $artist->tov_name = $label->tov_name;
        $artist->label_id = $label->id;
        $artist->admin_id = 1;
        $artist->name = $label->name;
        $artist->full_name = $label->full_name;
        $artist->contract = $label->contract;
        $artist->logo = $label->logo;
        $artist->phone = $label->phone;
        $artist->email = $label->email;
        $artist->active = $label->active;
        $artist->percentage = $label->percentage;
        $artist->percentage_distribution = $label->percentage_distribution;
        $artist->deposit = 0;
        $artist->deposit_1 = 0;
        $artist->deposit_3 = 0;
        $artist->telegram_id = $label->telegram_id;
        $artist->ipn = $label->ipn;
        $artist->edrpou = $label->edrpou;
        $artist->address = $label->address;
        $artist->iban = $label->iban;
        $artist->bank = $label->bank;
        $artist->mfo = $label->mfo;
        $artist->description = $label->description;
        $artist->notify = 0;
        
        if ($artist->save()) {
            $label->migrated = $artist->id;
            $label->save(false);
            
            exit('Змігровано');
        }
        
        print_r($artist->getErrors());*/
        
        exit(0);
    }
    
    public function actionActive(int $status = 0)
    {
        $query = "SELECT id, migrated FROM sub_label WHERE migrated > 0";
        $labels = Yii::$app->db->createCommand($query)->queryAll();
        
        foreach ($labels as $label) {
            $labelId = $label['id'];
            $id = $label['migrated'];
            $sql = "UPDATE `artist` SET `active`= {$status} WHERE label_id = {$labelId} and id != {$id}";
            Yii::$app->db->createCommand($sql)->execute();
        }
    }
    
    public function actionMigrateTrack(int $trackId, int $toArtistId)
    {
        if (empty($trackId) || empty($toArtistId)) {
            exit('Empty trackId, to artistId');
        }
        
        $track = Track::findOne($trackId);
        
        if (is_null($track)) {
            exit('Error track');
        }
        
        try {
            $track->migrateToArtist($toArtistId);
        } catch (\Throwable $e) {
            exit($e->getMessage());
        }
        
        echo 'Ok' . PHP_EOL;
        exit(0);
    }
    
    public function actionFix()
    {
        Yii::$app->db->beginTransaction();
        
        $overAllocated = Yii::$app->db->createCommand("
            SELECT
                ia.payout_item_id,
                ABS(ii.amount) AS payout_amount,
                SUM(ia.amount) AS allocated_amount
            FROM invoice_allocation ia
            JOIN invoice_items ii ON ii.id = ia.payout_item_id
            GROUP BY ia.payout_item_id
            HAVING allocated_amount > payout_amount
        ")->queryAll();
        
        if (empty($overAllocated)) {
            echo "✅ No over-allocated payout items found\n";
            return;
        }
        
        echo "⚠ Found " . count($overAllocated) . " payout items with over-allocation\n";
        
        foreach ($overAllocated as $row) {
            $payoutItemId   = (int)$row['payout_item_id'];
            $payoutAmount   = (float)$row['payout_amount'];
            
            echo "\n▶ Fixing payout_item_id = {$payoutItemId}, amount = {$payoutAmount}\n";
            
            // ✅ Отримуємо payout_item
            $payoutItem = InvoiceItems::findOne($payoutItemId);
            if (!$payoutItem) {
                echo "  ❌ payout item not found\n";
                continue;
            }
            
            $artistId = $payoutItem->artist_id;
            
            Yii::$app->db->createCommand()
                ->delete('invoice_allocation', [
                    'payout_item_id' => $payoutItemId
                ])->execute();
            
            echo "  🧹 allocations deleted\n";
            
            $incomeItems = Yii::$app->db->createCommand("
                SELECT ii.id, ii.amount, i.year, i.quarter
                FROM invoice_items ii
                JOIN invoice i ON i.invoice_id = ii.invoice_id
                WHERE ii.artist_id = :artist_id
                  AND ii.amount > 0
                  AND i.invoice_type IN (1,5)
                ORDER BY i.year ASC, i.quarter ASC, ii.id ASC
            ")->bindValue(':artist_id', $artistId)->queryAll();
            
            $remaining = $payoutAmount;
            foreach ($incomeItems as $incomeRow) {
                
                if ($remaining <= 0) {
                    break;
                }
                
                $incomeItemId = (int)$incomeRow['id'];
                
                // ✅ unpaid income
                $unpaidIncome = (float) Yii::$app->db->createCommand("
            SELECT ii.amount - IFNULL(SUM(ia.amount), 0)
            FROM invoice_items ii
            LEFT JOIN invoice_allocation ia
              ON ia.income_item_id = ii.id
            WHERE ii.id = :id
            GROUP BY ii.amount
        ")->bindValue(':id', $incomeItemId)->queryScalar();
                
                if ($unpaidIncome <= 0) {
                    continue;
                }
                
                $allocAmount = min($unpaidIncome, $remaining);
                
                Yii::$app->db->createCommand()
                    ->insert('invoice_allocation', [
                    'income_item_id' => $incomeItemId,
                    'payout_item_id' => $payoutItemId,
                    'amount'         => $allocAmount,
                ])->execute();
                
                echo "  ✔ allocated {$allocAmount} from income_item {$incomeItemId}\n";
                
                $remaining -= $allocAmount;
            }
            
            if ($remaining > 0) {
                echo "  ⚠ WARNING: payout_item {$payoutItemId} still has {$remaining} unallocated\n";
            } else {
                echo "  ✅ payout_item {$payoutItemId} fixed successfully\n";
            }
        }
        
        Yii::$app->db->transaction->commit();
    }
    
    
    public function actionMig()
    {
        echo "🚀 Rebuilding invoice_allocation with strict FIFO logic\n";
        
        Yii::$app->db->beginTransaction();
        
        // 1️⃣ Отримуємо ВСІ payout_items
        $payoutItems = (new Query())
            ->select([
                'ii.id',
                'ii.artist_id',
                'ABS(ii.amount) AS payout_amount',
            ])
            ->from('invoice_items ii')
            ->innerJoin('invoice i', 'i.invoice_id = ii.invoice_id')
            ->where(['i.invoice_type' => [2, 3]])
            ->andWhere(['<', 'ii.amount', 0])
            ->orderBy(['ii.id' => SORT_ASC])
            ->all();
        
        echo "🔍 Found " . count($payoutItems) . " payout items\n";
        
        foreach ($payoutItems as $payout) {
            
            $payoutItemId = (int)$payout['id'];
            $artistId     = (int)$payout['artist_id'];
            $remaining    = (float)$payout['payout_amount'];
            
            echo "\n▶ Payout item {$payoutItemId}, artist {$artistId}, amount {$remaining}\n";
            
            // 2️⃣ Видаляємо стару allocation для цього payout_item
            $deleted = Yii::$app->db->createCommand()
                ->delete('invoice_allocation', [
                    'payout_item_id' => $payoutItemId
                ])->execute();
            echo "  🧹 Deleted {$deleted} old allocation rows\n";
            
            // 3️⃣ Income items FIFO
            $incomeItems = (new Query())
                ->select([
                    'ii.id',
                    'ii.amount',
                    'i.year',
                    'i.quarter',
                ])
                ->from('invoice_items ii')
                ->innerJoin('invoice i', 'i.invoice_id = ii.invoice_id')
                ->where([
                    'ii.artist_id' => $artistId,
                    'i.invoice_type' => [1,5],
                ])
                ->andWhere(['>', 'ii.amount', 0])
                ->orderBy([
                    'i.year' => SORT_ASC,
                    'i.quarter' => SORT_ASC,
                    'ii.id' => SORT_ASC,
                ])
                ->all();
            
            foreach ($incomeItems as $income) {
                
                if ($remaining <= 0) {
                    break;
                }
                
                $incomeItemId = (int)$income['id'];
                
                // 4️⃣ unpaid income
                $unpaidIncome = (float) (new Query())
                    ->select(['ii.amount - COALESCE(SUM(ia.amount),0)'])
                    ->from('invoice_items ii')
                    ->leftJoin(
                        'invoice_allocation ia',
                        'ia.income_item_id = ii.id'
                    )
                    ->where(['ii.id' => $incomeItemId])
                    ->scalar();
                
                if ($unpaidIncome <= 0) {
                    continue;
                }
                
                $allocAmount = min($unpaidIncome, $remaining);
                
                // 5️⃣ Insert allocation
                Yii::$app->db->createCommand()
                    ->insert('invoice_allocation', [
                        'income_item_id' => $incomeItemId,
                        'payout_item_id' => $payoutItemId,
                        'amount'         => $allocAmount,
                    ])->execute();
                
                echo "    ✔ Allocated {$allocAmount} from income_item {$incomeItemId}\n";
                
                $remaining -= $allocAmount;
            }
            
            if ($remaining > 0) {
                echo "  ⚠ WARNING: payout_item {$payoutItemId} still unallocated {$remaining}\n";
            } else {
                echo "  ✅ payout_item {$payoutItemId} allocated successfully\n";
            }
        }
        Yii::$app->db->transaction->commit();
        
        echo "\n✅ Allocation rebuild completed successfully\n";
    }
    
    public function actionPay() {
        
        
            $db = Yii::$app->db;
            
            $transaction = $db->beginTransaction();
            try {
                
                // 1️⃣ Беремо всі нерозподілені payout / advance
                $payoutItems = $db->createCommand("
                SELECT
                    v_unallocated_payout_items.payout_item_id,
                    v_unallocated_payout_items.artist_id,
                    v_unallocated_payout_items.currency_id,
                    v_unallocated_payout_items.invoice_type,
                    v_unallocated_payout_items.unallocated_amount,
                    ii.invoice_id,
                    i.quarter, i.year
                FROM v_unallocated_payout_items
                inner join invoice_items ii ON ii.id = payout_item_id
                left join invoice i ON i.invoice_id = ii.invoice_id
                WHERE unallocated_amount > 0
                ORDER BY ii.invoice_id, artist_id, currency_id, payout_item_id
            ")->queryAll();
                
                foreach ($payoutItems as $payout) {
                    $payoutItemId   = (int) $payout['payout_item_id'];
                    $artistId       = (int) $payout['artist_id'];
                    $currencyId     = (int) $payout['currency_id'];
                    $remaining      = (float) $payout['unallocated_amount'];
                    $invoice_id      = (int) $payout['invoice_id'];
                    $quarter      = (int) $payout['quarter'];
                    $year     = (int) $payout['year'];
                    
                    // 2️⃣ FIFO income тільки цього артиста і валюти
                    $incomeItems = $db->createCommand("
                    SELECT
                        income_item_id,
                        unpaid_amount,
                        invoice_id
                    FROM v_income_balance
                    WHERE artist_id = :artist_id
                      AND currency_id = :currency_id
                      AND unpaid_amount > 0
                        and year <= :year
                    and quarter <= :quarter
                    ORDER BY year, quarter, income_item_id
                ")->bindValues([
                        ':artist_id'   => $artistId,
                        ':currency_id' => $currencyId,
                        ':year' => $year,
                        ':quarter' => $quarter,
                    ])->queryAll();
                    
                    foreach ($incomeItems as $income) {
                        
                        if ($remaining <= 0) {
                            break;
                        }
                        
                        $incomeItemId = (int) $income['income_item_id'];
                        $unpaidIncome = (float) $income['unpaid_amount'];
                        
                        if ($unpaidIncome <= 0) {
                            continue;
                        }
                        
                        $allocAmount = min($remaining, $unpaidIncome);
                        
                        // 3️⃣ Робимо allocation
                        $db->createCommand()
                            ->insert('invoice_allocation', [
                            'income_item_id' => $incomeItemId,
                            'payout_item_id' => $payoutItemId,
                            'amount'         => $allocAmount,
                        ])->execute();
                        
                        $remaining -= $allocAmount;
                    }
                }
                
                $transaction->commit();
                
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw new Exception('Allocation failed: ' . $e->getMessage(), 0, $e);
            }
    }
    
}