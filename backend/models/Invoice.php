<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoices".
 *
 * @property int $invoice_id
 * @property int $user_id
 * @property int $invoice_type
 * @property int $invoice_status_id
 * @property int $aggregator_id
 * @property int $aggregator_report_id
 * @property int|null $currency_id
 * @property float $total
 * @property string $date_added
 * @property string $last_update
 *
 * @property InvoiceItems[] $invoiceItems
 * @property Aggregator $aggregator
 * @property InvoiceType $invoiceType
 * @property Currency $currency
 */
class Invoice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_type', 'currency_id', 'user_id'], 'required'],
            [['invoice_type', 'aggregator_id', 'user_id', 'currency_id'], 'integer'],
            [['total'], 'number'],
            [['date_added', 'last_update'], 'safe'],
            [['aggregator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aggregator::class, 'targetAttribute' => ['aggregator_id' => 'aggregator_id']],
            [['invoice_type'], 'exist', 'skipOnError' => true, 'targetClass' => InvoiceType::class, 'targetAttribute' => ['invoice_type' => 'invoice_type_id']],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['invoice_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => InvoiceStatus::class, 'targetAttribute' => ['invoice_status_id' => 'invoice_status_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'invoice_id' => Yii::t('app', '№'),
            'user_id' => Yii::t('app', 'Додав'),
            'invoice_type' => Yii::t('app', 'Тип інвойсу'),
            'invoice_status_id' => Yii::t('app', 'Статус інвойсу'),
            'aggregator_id' => Yii::t('app', 'Агрегатор'),
            'currency_id' => Yii::t('app', 'Валюта'),
            'total' => Yii::t('app', 'Сума'),
            'date_added' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
            'ownership_type' => Yii::t('app', 'Тип Ввласності'),
        ];
    }

    /**
     * Gets query for [[InvoiceItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems()
    {
        return $this->hasMany(InvoiceItems::class, ['invoice_id' => 'invoice_id']);
    }

    /**
     * Gets query for [[Aggregator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregator()
    {
        return $this->hasOne(Aggregator::class, ['aggregator_id' => 'aggregator_id']);
    }

    /**
     * Gets query for [[InvoiceType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceType()
    {
        return $this->hasOne(InvoiceType::class, ['invoice_type_id' => 'invoice_type']);
    }

    /**
     * Gets query for [[Currency]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    /**
     * Gets query for [[InvoiceStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceStatus()
    {
        return $this->hasOne(InvoiceStatus::className(), ['invoice_status_id' => 'invoice_status_id']);
    }

    public function calculate()
    {
        $total = 0;

        foreach ($this->getInvoiceItems()->all() as $item) {
            $total += $item->amount;
        }

        if ($total != $this->total) {
            $this->total = $total;
            $this->save();
        }
    }
}
