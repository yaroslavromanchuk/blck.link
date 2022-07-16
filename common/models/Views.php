<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "views".
 *
 * @property int $id
 * @property int $track_id
 * @property int $view
 * @property string $country
 * @property string $data
 * @property string $referal
 * @property string $ip
 *
 * @property Track $track
 */
class Views extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'views';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['track_id', 'view', 'country', 'data', 'ip'], 'required'],
            [['track_id', 'view'], 'integer'],
            [['date'], 'safe'],
            [['country', 'ip', 'referal'], 'string', 'max' => 255],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\models\Track::className(), 'targetAttribute' => ['track_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'track_id' => Yii::t('app', 'Релиз'),
            'view' => Yii::t('app', 'View'),
            'country' => Yii::t('app', 'Страна'),
            'data' => Yii::t('app', 'Date'),
            'ip' => Yii::t('app', 'Ip'),
            'referal' => Yii::t('app', 'Пришёл'),
        ];
    }

    /**
     * Gets query for [[Track]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTrack()
    {
        return $this->hasOne(Track::className(), ['id' => 'track_id']);
    }
}
