<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * ActiveRecord model for view "v_unpaid_income_by_period"
 *
 * @property int $artist_id       ID артиста
 * @property int $currency_id     ID валюти
 * @property int $year            Рік нарахування
 * @property int $quarter         Квартал нарахування (1–4)
 * @property float $unpaid_total  Сума, що ще не виплачена
 */
class VUnpaidIncomeByPeriod extends ActiveRecord
{
    /**
     * Назва DB view
     */
    public static function tableName(): string
    {
        return 'v_unpaid_income_by_period';
    }
    
    /**
     * Вказуємо, що це view (тільки read-only)
     */
    public static function primaryKey(): array
    {
        // View зазвичай не має PK — задаємо композиційний
        return ['artist_id', 'currency_id', 'year', 'quarter'];
    }
    
    /**
     * Правила валідації
     */
    public function rules(): array
    {
        return [
            [['artist_id', 'currency_id', 'year', 'quarter'], 'integer'],
            [['unpaid_total'], 'number'],
            
            [['artist_id', 'currency_id', 'year', 'quarter'], 'required'],
        ];
    }
    
    /**
     * Людські назви атрибутів
     */
    public function attributeLabels(): array
    {
        return [
            'artist_id'     => 'Артист',
            'currency_id'   => 'Валюта',
            'year'          => 'Рік',
            'quarter'       => 'Квартал',
            'unpaid_total'  => 'Невиплачено',
        ];
    }
    
    /**
     * За потреби — звʼязок з валютою
     */
    public function getCurrency()
    {
        return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
    }
    
    /**
     * Отримати Q1–Q4 як текст
     */
    public function getQuarterLabel(): string
    {
        return 'Q' . $this->quarter;
    }
}
