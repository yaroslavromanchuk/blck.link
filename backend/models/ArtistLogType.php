<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "artist_log_type".
 *
 * @property int $log_type_id
 * @property int $name
 * @property string $date_add
 * @property string $last_update
 *
 * @property ArtistLog[] $artistLogs
 */
class ArtistLogType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'artist_log_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'integer'],
            [['date_add', 'last_update'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'log_type_id' => 'Log Type ID',
            'name' => 'Name',
            'date_add' => 'Date Add',
            'last_update' => 'Last Update',
        ];
    }

    /**
     * Gets query for [[ArtistLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtistLogs()
    {
        return $this->hasMany(ArtistLog::class, ['type_id' => 'log_type_id']);
    }
}