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
    // Константи типів інвойсів
    public const DEBIT = 1;           // Надходження від платформ
    public const PAY = 2;             // Виплата артистам
    public const COSTS = 3;           // Витрати (комісії, тощо)
    public const ADVANCE = 4;         // Авансові виплати
    public const CORRECTION = 5;      // Корекції балансу
    
    // Групи типів для запитів
    public const INCOME_TYPES = [self::DEBIT, self::CORRECTION];           // Доходи
    public const EXPENSE_TYPES = [self::COSTS, self::ADVANCE];             // Витрати
    public const PAYOUT_TYPES = [self::PAY];                               // Виплати
    public const ALLOCATION_EXPENSE_TYPES = [self::COSTS, self::ADVANCE];  // Витрати для алокації
    
    // Стара API для зворотної сумісності
    public static int $debit = self::DEBIT;
    public static int $credit = self::PAY;
    public static int $costs = self::COSTS;
    public static int $advance = self::ADVANCE;
    public static int $balance = self::CORRECTION;
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
