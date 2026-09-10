<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_bonus".
 *
 * @property int $id
 * @property int $user_id
 * @property int $label_id
 * @property int $artist_id
 * @property int $track_id
 * @property int $percentage
 * @property string $date_added
 * @property string $last_update
 *
 * @property SubLabel $label
 * @property Artist $artist
 * @property Track $track
 * @property User $user
 */
class UserBonus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_bonus';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'label_id', 'artist_id', 'track_id', 'percentage'], 'integer'],
            [['date_added', 'last_update'], 'safe'],
            [['label_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubLabel::class, 'targetAttribute' => ['label_id' => 'id']],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
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
            'label_id' => 'Label ID',
            'artist_id' => 'Контрагент',
            'track_id' => 'Трек',
            'percentage' => 'Відсоток',
            'date_added' => 'Додано',
            'last_update' => 'Оновлено',
        ];
    }
    
    public function getLabel()
    {
        return $this->hasOne(SubLabel::class, ['id' => 'label_id']);
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
    
    public static function getUserToLabel(int $labelId): array
    {
        return self::find()->where(['label_id' => $labelId])->all();
    }
    
    public static function getUserToArtist(int $artistId): array
    {
        return self::find()->where(['artist_id' => $artistId])->all();
    }
    
    public static function getUserToTrack(int $trackId): array
    {
        return self::find()->where(['track_id' => $trackId])->all();
    }
}