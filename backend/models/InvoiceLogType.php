<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoice_log_type".
 *
 * @property int $log_type_id
 * @property string $name
 * @property string $date_add
 * @property string $last_update
 *
 * @property InvoiceLog[] $invoiceLogs
 */
class InvoiceLogType extends \yii\db\ActiveRecord
{
    public const EMAIL = 1;
    public const APPROVED = 2;
    public const PAYED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_log_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'log_type_id' => 'Log Type ID',
            'name' => 'Name',
            'date_add' => 'Date Add',
            'last_update' => 'Last Update',
        ];
    }

    /**
     * Gets query for [[InvoiceLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceLogs()
    {
        return $this->hasMany(InvoiceLog::class, ['log_type_id' => 'log_type_id']);
    }
}
