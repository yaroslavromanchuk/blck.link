<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoice_type".
 *
 * @property int $invoice_type_id
 * @property string $invoice_type_name
 * @property string $date_add
 * @property string $last_update
 *
 * @property Invoice[] $invoices
 */
class InvoiceType extends \yii\db\ActiveRecord
{
    public static int $debit = 1; // Надходження
    public static int $credit = 2; // Виплата
    public static int $costs = 3;
    public static int $advance = 4;
    public static int $balance = 4;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_type_name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['invoice_type_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'invoice_type_id' => Yii::t('app', 'Інвойс Тип ID'),
            'invoice_type_name' => Yii::t('app', 'Тип інвойсу'),
            'date_add' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::className(), ['invoice_type' => 'invoice_type_id']);
    }
}
