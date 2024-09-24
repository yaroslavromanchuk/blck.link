<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ownership".
 *
 * @property int $id
 * @property string $name
 * @property string $date_add
 * @property string $last_update
 *
 * @property Aggregator[] $aggregators
 * @property OwnershipType[] $ownershipTypes
 */
class Ownership extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ownership';
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
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Власність'),
            'date_add' => Yii::t('app', 'Дата створення'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[Aggregators]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregators()
    {
        return $this->hasMany(Aggregator::class, ['ownership_type' => 'id']);
    }

    /**
     * Gets query for [[OwnershipTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwnershipTypes()
    {
        return $this->hasMany(OwnershipType::class, ['ownership' => 'id']);
    }
}
