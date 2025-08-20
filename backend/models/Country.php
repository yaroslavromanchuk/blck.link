<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "country".
 *
 * @property int $country_id
 * @property string $country_name
 * @property string $date_added
 * @property string $last_update
 *
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country_id', 'country_name'], 'required'],
            [['country_id'], 'integer'],
            [['country_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'country_id' => 'ID Країни',
            'country_name' => 'Назва Країни',
            'date_added' => 'Додано',
            'last_update' => 'Останнє оновлення',
        ];
    }
}