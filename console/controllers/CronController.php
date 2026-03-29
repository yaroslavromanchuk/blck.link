<?php

namespace console\controllers;

use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\SubLabel;
use backend\models\Track;
use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class CronController extends Controller
{
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
}