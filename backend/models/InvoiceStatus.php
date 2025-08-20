<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoice_status".
 *
 * @property int $invoice_status_id
 * @property string $invoice_status_name
 * @property string $date_add
 * @property string $last_update
 *
 * @property Invoice[] $invoices
 */
class InvoiceStatus extends \yii\db\ActiveRecord
{

    public const Generated = 1;
    public const Calculated = 2;
    public const Error = 3;
    public const InProgress = 4;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_status_name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['invoice_status_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'invoice_status_id' => Yii::t('app', 'Invoice Status ID'),
            'invoice_status_name' => Yii::t('app', 'Invoice Status Name'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['invoice_status_id' => 'invoice_status_id']);
    }
}