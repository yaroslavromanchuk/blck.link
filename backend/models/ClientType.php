<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "client_type".
 *
 * @property int $type_id
 * @property string $name
 * @property string $date_added
 * @property string $last_update
 *
 * @property Artist[] $artists
 */
class ClientType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date_added', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'type_id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Тип партнерства'),
            'date_added' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[Artists]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtists()
    {
        return $this->hasMany(Artist::class, ['type_id' => 'type_id']);
    }
}