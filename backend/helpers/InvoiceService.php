<?php

namespace backend\helpers;


use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use Yii;
use yii\db\Exception;

class InvoiceService
{
    /**
     * Створює інвойс виплати на основі депозитів артистів
     * 
     * @param array $artistIds - ID артистів для виплати
     * @param array $data - Дані інвойсу (quarter, year, currency_id, invoice_type, user_id, label_id)
     * @return int - ID створеного інвойсу
     * @throws \Throwable
     * @throws Exception
     */
    public static function createPayFromArtists(array $artistIds, array $data = []): int
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Отримати всіх художників
            $artists = Artist::find()
                ->where(['in', 'id', $artistIds])
                ->all();
            
            if (!$artists) {
                throw new \Exception('Артисти не знайдені');
            }
            
            // Створити інвойс
            $invoice = new Invoice();
            $invoice->load($data);
            $invoice->date_added = date('Y-m-d');
            $invoice->invoice_status_id = InvoiceStatus::Generated;
            $invoice->description = 'Виплата за ' . $invoice->quarter . 'кв ' . $invoice->year;
            
            if (!$invoice->save()) {
                $errors = $invoice->getErrors();
                throw new \Exception('Не вдалося створити інвойс: ' . current($errors));
            }
            
            // Додати позиції в інвойс
            $sum = 0.0;
            foreach ($artists as $artist) {
                $amount = $artist->getDep($invoice->currency_id) ?? 0;
                
                // Для витрат, авансів та кредитів робити суму від'ємною
                if ($amount != 0.00
                    && in_array($invoice->invoice_type, [InvoiceType::$credit, InvoiceType::$costs, InvoiceType::$advance])
                ) {
                    $amount *= -1;
                }
                
                $row = new InvoiceItems();
                $row->invoice_id = $invoice->invoice_id;
                $row->artist_id = $artist->id;
                $row->amount = $amount;
                $row->date_item = date('Y-m-d');
                
                $sum += $amount;
                
                if (!$row->save()) {
                    $errors = $row->getErrors();
                    throw new \Exception('Помилка додавання запису в інвойс: ' . current($errors));
                }
            }
            
            // Оновити загальну суму інвойсу
            $invoice->total = $sum;
            $invoice->save(false);
            
            $transaction->commit();
            
            return $invoice->invoice_id;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
