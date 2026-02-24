<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_service_type".
 *
 * @property int $service_type_id
 * @property string $name
 * @property string $date_added
 * @property string $last_update
 */
class AggregatorServiceType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_service_type';
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
            'service_type_id' => 'ID',
            'name' => 'Тип діяльності',
            'date_added' => 'Додано',
            'last_update' => 'Оновлено',
        ];
    }
}