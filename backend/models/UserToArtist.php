<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_to_artist".
 *
 * @property int $id
 * @property int $user_id
 * @property int $artist_id
 * @property int $percentage
 * @property string $date_added
 * @property string $last_update
 *
 * @property Artist $artist
 * @property User $user
 */
class UserToArtist extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_to_artist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'artist_id'], 'required'],
            [['user_id', 'artist_id', 'percentage'], 'integer'],
            [['date_added', 'last_update'], 'safe'],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
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
            'artist_id' => 'Artist ID',
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
    public function getArtist()
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
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