<?php

namespace backend\models;

use Yii;

/**
 * @property int track_id
 * @property int artist_id
 * @property int percentage
 */
class Percentage extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'track_to_percentage';
    }
    public function rules(): array
    {
        return [
            [['track_id', 'artist_id', 'percentage'], 'required'],
            [['track_id', 'artist_id', 'percentage'], 'integer'],
            //[['percentage'], 'max' => 100],
           // [['percentage'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true, 'targetClass' => Percentage::class, 'targetAttribute' => ['track_id' => 'id'], 'message' => Yii::t('app', 'Сума відсотів не може перевищувати 100%')],
            [['percentage'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
        ];
    }
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', '№'),
            'track_id' => Yii::t('app', 'Трек'),
            'artist_id' => Yii::t('app', 'Артист'),
            'percentage' => Yii::t('app', 'Відсоток'),
        ];
    }
}