<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregators".
 *
 * @property int $aggregator_id
 * @property string $Name
 * @property int|null $currency
 * @property string $date_add
 * @property string $last_update
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
            [['Name'], 'required'],
            [['currency'], 'integer'],
            [['date_add', 'last_update'], 'safe'],
            [['Name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aggregator_id' => Yii::t('app', 'Aggregator ID'),
            'Name' => Yii::t('app', 'Name'),
            'currency' => Yii::t('app', 'Currency'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
}
