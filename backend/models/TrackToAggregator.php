<?php

namespace backend\models;

use Yii;

/**
* This is the model class for table "track_to_aggregator".
*
* @property int $aggregator_id
* @property int $track_id
* @property string $first_seen_at
*
* @property Aggregator $aggregator
* @property Track $track
*/
class TrackToAggregator extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'track_to_aggregator';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aggregator_id', 'track_id'], 'required'],
            [['aggregator_id', 'track_id'], 'integer'],
            [['first_seen_at'], 'safe'],
            [['aggregator_id', 'track_id'], 'unique', 'targetAttribute' => ['aggregator_id', 'track_id']],
            [['aggregator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aggregator::class, 'targetAttribute' => ['aggregator_id' => 'aggregator_id']],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aggregator_id' => Yii::t('app', 'Aggregator ID'),
            'track_id' => Yii::t('app', 'Track ID'),
            'first_seen_at' => Yii::t('app', 'First Seen At'),
        ];
    }
    
    /**
     * Gets query for [[Aggregator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregator()
    {
        return $this->hasOne(Aggregator::class, ['aggregator_id' => 'aggregator_id']);
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
}