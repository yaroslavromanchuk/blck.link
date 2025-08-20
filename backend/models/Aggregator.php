<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregators".
 *
 * @property int $aggregator_id
 * @property string $name
 * @property string|null $description
 * @property int $ownership_type
 * @property int $type_use_id
 * @property int $service_id
 * @property int|null $currency_id
 * @property string $date_add
 * @property string $last_update
 *
 * @property AggregatorReportItem[] $aggregatorReports
 * @property Ownership $ownershipType
 * @property Currency $currency
 * @property AggregatorTypeUse $type
 * @property AggregatorService $service
 */
class Aggregator extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['ownership_type', 'currency_id', 'type_use_id', 'service_id'], 'integer'],
            [['date_add', 'last_update'], 'safe'],
            [['name', 'description'], 'string', 'max' => 255],
            [['ownership_type'], 'exist', 'skipOnError' => true, 'targetClass' => Ownership::className(), 'targetAttribute' => ['ownership_type' => 'id']],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::className(), 'targetAttribute' => ['currency_id' => 'currency_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aggregator_id' => Yii::t('app', 'Aggregator ID'),
            'name' => Yii::t('app', 'Назва'),
            'description' => Yii::t('app', 'Деталі'),
            'ownership_type' => Yii::t('app', 'Тип Ввласності'),
            'currency_id' => Yii::t('app', 'Валюта'),
            'type_use_id' => Yii::t('app', 'Тип використання'),
            'service_id' => Yii::t('app', 'Ресурс використання'),
            'date_add' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[AggregatorReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregatorReports()
    {
        return $this->hasMany(AggregatorReportItem::class, ['aggregator_id' => 'aggregator_id']);
    }

    /**
     * Gets query for [[OwnershipType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwnershipType()
    {
        return $this->hasOne(Ownership::class, ['id' => 'ownership_type']);
    }

    public function getType()
    {
        return $this->hasOne(AggregatorTypeUse::class, ['type_id' => 'type_use_id']);
    }
    public function getService()
    {
        return $this->hasOne(AggregatorService::class, ['service_id' => 'service_id']);
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
}
