<?php

namespace backend\helpers;


use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use Yii;
use yii\db\Exception;

class InvoiceService
{
    /**
     * @throws \Throwable
     * @throws Exception
     */
    public static function createPayFromArtists(array $artistIds, array $data = []): int
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $artists = Artist::find()
                //->where(['id' => $artistIds])
                ->where(['in', 'id', $artistIds])
                ->all();
            
            if (!$artists) {
                throw new \Exception('Артисти не знайдені');
            }
            
            $invoice = new Invoice();
            $invoice->load($data);
            $invoice->date_added = date('Y-m-d');
            $invoice->invoice_status_id = InvoiceStatus::Generated;
            $invoice->description = 'Виплата за ' .$invoice->quarter . 'кв ' . $invoice->year;
            
            if (!$invoice->save()) {
                $errors = $invoice->getErrors();
                throw new \Exception('Не вдалося створити інвойс:' . current($errors));
            }
            
            foreach ($artists as $artist) {
                $row = new InvoiceItems();
                $row->invoice_id = $invoice->invoice_id;
                $row->artist_id = $artist->id;
                $row->amount = $artist->getDep($invoice->currency_id) ?? 0 * -1;
                $row->date_item = date('Y-m-d');
                
                if (!$row->save()) {
                    $errors = $row->getErrors();
                    throw new \Exception('Помилка додаваня запису в інвойс інвойсу на виплату: ' . current($errors));
                }
            }
            
            $transaction->commit();
            
            return $invoice->invoice_id;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
