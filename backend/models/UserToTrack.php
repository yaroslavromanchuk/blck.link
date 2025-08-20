<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_to_track".
 *
 * @property int $id
 * @property int $user_id
 * @property int $track_id
 * @property int $percentage
 * @property string $date_added
 * @property string $last_update
 *
 * @property Track $track
 * @property User $user
 */
class UserToTrack extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_to_track';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'track_id'], 'required'],
            [['user_id', 'track_id', 'percentage'], 'integer'],
            [['date_added', 'last_update'], 'safe'],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'track_id' => 'Track ID',
            'percentage' => 'Percentage',
            'date_added' => 'Date Added',
            'last_update' => 'Last Update',
        ];
    }

    /**
     * Gets query for [[Track]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTrack()
    {
        return $this->hasOne(Track::class, ['id' => 'track_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}