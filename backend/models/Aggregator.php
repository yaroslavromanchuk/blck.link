<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregators".
 *
 * @property int $aggregator_id
 * @property string $name
 * @property int|null $currency_id
 * @property string $date_add
 * @property string $last_update
 *
 * @property Currency[] $currency
 */
class Aggregator extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregators';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['currency_id'], 'integer'],
            [['date_add', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aggregator_id' => Yii::t('app', '№'),
            'name' => Yii::t('app', 'Назва'),
            'currency_id' => Yii::t('app', 'Валюта'),
            'date_add' => Yii::t('app', 'Створено'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    public function getCurrency(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
    }
}
