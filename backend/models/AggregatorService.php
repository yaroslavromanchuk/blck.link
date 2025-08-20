<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_service".
 *
 * @property int $service_id
 * @property string $name
 * @property string $date_added
 * @property string $last_update
 */
class AggregatorService extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_service';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date_added', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'service_id' => 'ID',
            'name' => 'Ресурс використання',
            'date_added' => 'Додано',
            'last_update' => 'Оновлено',
        ];
    }
}