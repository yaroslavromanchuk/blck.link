<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "track_to_album".
 *
 * @property int $id
 * @property int $album_id
 * @property int $track_id
 * @property string $date_added
 * @property string $last_update
 */
class TrackToAlbum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'track_to_album';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['album_id', 'track_id'], 'required'],
            [['album_id', 'track_id'], 'integer'],
            [['date_added', 'last_update'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'album_id' => 'Album ID',
            'track_id' => 'Track ID',
            'date_added' => 'Date Added',
            'last_update' => 'Last Update',
        ];
    }
}
