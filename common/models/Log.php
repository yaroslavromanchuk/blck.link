<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "log".
 *
 * @property int $id
 * @property int $track
 * @property string $type
 * @property string $name
 * @property string $referal
 * @property string|null $ip
 * @property string|null $country
 * @property string $data
 *
 * @property Track $track0
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['track', 'type', 'name', 'referal', 'data'], 'required'],
            [['track'], 'integer'],
            [['data'], 'safe'],
            [['type'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 50],
            [['referal', 'ip', 'country'], 'string', 'max' => 255],
            [['track'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\models\Track::className(), 'targetAttribute' => ['track' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'track' => Yii::t('app', 'Релиз'),
            'type' => Yii::t('app', 'Тип'),
            'name' => Yii::t('app', 'Сервис'),
            'referal' => Yii::t('app', 'Пришёл'),
            'ip' => Yii::t('app', 'IР'),
            'ip' => Yii::t('app', 'Страна'),
            'data' => Yii::t('app', 'Дата'),
        ];
    }

    /**
     * Gets query for [[Track0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTracks()
    {
        return $this->hasOne(\backend\models\Track::className(), ['id' => 'track']);
    }
}
