<?php

namespace backend\models;

use Yii;
use yii\base\Model;

class InvoiceReport extends Model
{
    public $data = null;
    public $invoiceId = null;
    public $groupBy = 't.artist_id';
    public $orderBy = 'amount DESC';
    public $limit = 30;

    public function rules(): array
    {
        return [
            [['data', 'invoiceId', 'groupBy', 'orderBy'], 'required'],
            [['data'], 'each', 'rule' => ['string', 'min' => 1]],
            [['invoiceId'], 'each', 'rule' => ['integer', 'min' => 1]],
            [['groupBy', 'orderBy'], 'string', 'max' => 255],
            [['limit'], 'integer', 'min' => 1, 'max' => 100],
            //[['groupBy', 'orderBy'], 'in', 'range' => [0, 1, 2]], // Assuming 0, 1, 2 are valid values for groupBy and orderBy
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'data' => Yii::t('app', 'Дані'),
            'invoiceId' => Yii::t('app', 'Інвойси'),
            'groupBy' => Yii::t('app', 'Групувати за'),
            'orderBy' => Yii::t('app', 'Сортувати за'),
            'limit' => Yii::t('app', 'Ліміт'),
        ];
    }

}