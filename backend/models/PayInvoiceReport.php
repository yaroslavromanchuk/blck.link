<?php

namespace backend\models;

use Yii;
use yii\base\Model;

/**
 * PayInvoiceReport is the model behind the report form of `\backend\controllers\PayInvoiceController`.
 *
 * @property int|null $invoiceId
 * @property int|null $year
 * @property int|null $quarter
 */
class PayInvoiceReport extends Model
{
    public $invoiceId = null;
    public $year = null;
    public $quarter = null;

    public function rules(): array
    {
        return [
           // [['invoiceId'], 'each', 'rule' => ['integer', 'min' => 1]],
            //[['invoiceId'], 'safe'],
            [['invoiceId', 'year', 'quarter'], 'integer'],
            [['year', 'quarter'], 'required', 'when' => function($model) {
                return $model->invoiceId == '';
            },  'whenClient' => "function (attribute, value) {
                return $('#invoiceId').val() == '';
            }"],
            [['year'], 'integer', 'min' => 2024, 'max' => (int)date('Y')],
            [['quarter'], 'integer', 'min' => 1, 'max' => 4],
           // [['limit'], 'integer', 'min' => 1, 'max' => 100],
            //[['groupBy', 'orderBy'], 'in', 'range' => [0, 1, 2]], // Assuming 0, 1, 2 are valid values for groupBy and orderBy
        ];
    }

    public function attributeLabels(): array
    {
        return [
            //'data' => Yii::t('app', 'Дані'),
            'invoiceId' => Yii::t('app', 'Інвойс'),
            'year' => Yii::t('app', 'Рік'),
            'quarter' => Yii::t('app', 'Квартал'),
            //'limit' => Yii::t('app', 'Ліміт'),
        ];
    }

}