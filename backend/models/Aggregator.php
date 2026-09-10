<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregators".
 *
 * @property int $aggregator_id
 * @property int $service_type_id
 * @property int|null $type_use_id
 * @property int|null $service_id
 * @property string $name
 * @property string|null $description
 * @property int $ownership_type
 * @property int|null $internal_type
 * @property int|null $currency_id
 * @property int|null $label_id
 * @property string $date_add
 * @property string $last_update
 *
 * @property AggregatorReport[] $aggregatorReports
 * @property AggregatorToOwnershipType[] $aggregatorToOwnershipTypes
 * @property Currency $currency
 * @property SubLabel $label
 * @property Invoice[] $invoices
 * @property Ownership $ownershipType
 * @property AggregatorService $service
 * @property AggregatorServiceType $serviceType
 * @property AggregatorTypeUse $type
 */
class Aggregator extends \yii\db\ActiveRecord
{
    
    
    /** many-to-many */
    public string|array $ownership_types = [];

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
            [['service_type_id', 'type_use_id', 'service_id', 'ownership_type', 'internal_type', 'currency_id', 'label_id'], 'integer'],
            [['name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['name', 'description'], 'string', 'max' => 255],
            [['ownership_type'], 'exist', 'skipOnError' => true, 'targetClass' => Ownership::class, 'targetAttribute' => ['ownership_type' => 'id']],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['service_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => AggregatorServiceType::class, 'targetAttribute' => ['service_type_id' => 'service_type_id']],
            [['type_use_id'], 'exist', 'skipOnError' => true, 'targetClass' => AggregatorTypeUse::class, 'targetAttribute' => ['type_use_id' => 'type_id']],
            [['service_id'], 'exist', 'skipOnError' => true, 'targetClass' => AggregatorService::class, 'targetAttribute' => ['service_id' => 'service_id']],
            [['label_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubLabel::class, 'targetAttribute' => ['label_id' => 'id']],
            [['ownership_types'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aggregator_id' => Yii::t('app', 'Aggregator ID'),
            'service_type_id' => Yii::t('app', 'Тип діяльності'),
            'name' => Yii::t('app', 'Назва'),
            'description' => Yii::t('app', 'Деталі'),
            'ownership_type' => Yii::t('app', 'Тип Власності'),
            'currency_id' => Yii::t('app', 'Валюта'),
            'type_use_id' => Yii::t('app', 'Тип використання'),
            'service_id' => Yii::t('app', 'Ресурс використання'),
            'date_add' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
            'ownership_types' => Yii::t('app', 'Тип Виконання'),
            'label_id' => Yii::t('app', 'Лейбл')
        ];
    }
    
    /**
     * Gets query for [[AggregatorReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregatorReports()
    {
        return $this->hasMany(AggregatorReport::class, ['aggregator_id' => 'aggregator_id']);
    }
    

    public function getType()
    {
        return $this->hasOne(AggregatorTypeUse::class, ['type_id' => 'type_use_id']);
    }
    
    /**
     * Gets query for [[AggregatorToOwnershipTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregatorToOwnershipTypes()
    {
        return $this->hasMany(AggregatorToOwnershipType::class, ['aggregator_id' => 'aggregator_id']);
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
    
    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['aggregator_id' => 'aggregator_id']);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLabel(): \yii\db\ActiveQuery
    {
        return $this->hasOne(SubLabel::class, ['id' => 'label_id']);
    }
    
    /**
     * Gets query for [[Service]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getService()
    {
        return $this->hasOne(AggregatorService::class, ['service_id' => 'service_id']);
    }
    
    /**
     * Gets query for [[ServiceType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServiceType()
    {
        return $this->hasOne(AggregatorServiceType::class, ['service_type_id' => 'service_type_id']);
    }
}
