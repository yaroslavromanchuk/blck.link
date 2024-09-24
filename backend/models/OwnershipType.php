<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ownership_type".
 *
 * @property int $id
 * @property int $ownership_id
 * @property string $name
 *
 * @property Ownership $ownership
 * @property Percentage $percentage
 */
class OwnershipType extends \yii\db\ActiveRecord
{

    public const Text = 1;
    public const Music = 2;
    public const Implementation = 3;
    public const Phonogram = 4;
    public const Total = 5;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ownership_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ownership_id', 'name'], 'required'],
            [['ownership_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['ownership_id'], 'exist', 'skipOnError' => true, 'targetClass' => Ownership::class, 'targetAttribute' => ['ownership_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'ownership_id' => Yii::t('app', 'Власність'),
            'name' => Yii::t('app', 'Категорія'),
        ];
    }

    /**
     * Gets query for [[Ownership0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwnership()
    {
        return $this->hasOne(Ownership::class, ['id' => 'ownership_id']);
    }

    /**
     * Gets query for [[TrackToPercentages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTrackToPercentages()
    {
        return $this->hasMany(Percentage::class, ['ownership_type' => 'id']);
    }
}
