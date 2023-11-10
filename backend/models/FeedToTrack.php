<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "feed_to_track".
 *
 * @property int $feed_id
 * @property int $track_id
 * @property int $artist_id
 * @property string $date_added
 * @property string $last_update
 */
class FeedToTrack extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feed_to_track';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['track_id', 'artist_id'], 'required'],
            [['track_id', 'artist_id'], 'integer'],
            [['date_added', 'last_update'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'feed_id' => Yii::t('app', 'Feed ID'),
            'track_id' => Yii::t('app', 'Track ID'),
            'artist_id' => Yii::t('app', 'Artist ID'),
            'date_added' => Yii::t('app', 'Date Added'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
}
