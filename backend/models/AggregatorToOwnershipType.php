<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_to_ownership_type".
 *
 * @property int $id
 * @property int $aggregator_id
 * @property int $ownership_type_id
 *
 * @property Aggregator $aggregator
 * @property OwnershipType $ownershipType
 */
class AggregatorToOwnershipType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_to_ownership_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aggregator_id', 'ownership_type_id'], 'required'],
            [['aggregator_id', 'ownership_type_id'], 'integer'],
            [['aggregator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aggregator::class, 'targetAttribute' => ['aggregator_id' => 'aggregator_id']],
            [['ownership_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => OwnershipType::class, 'targetAttribute' => ['ownership_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aggregator_id' => 'Aggregator ID',
            'ownership_type_id' => 'Ownership Type ID',
        ];
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
     * Gets query for [[OwnershipType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwnershipType()
    {
        return $this->hasOne(OwnershipType::class, ['id' => 'ownership_type_id']);
    }
}
