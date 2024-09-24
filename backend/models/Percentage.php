<?php

namespace backend\models;

use Yii;

/**
 * @property int id
 * @property int track_id
 * @property int artist_id
 * @property int percentage
 * @property int ownership_type
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

    /**
     * Gets query for [[OwnershipType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwnershipType()
    {
        return $this->hasOne(OwnershipType::class, ['id' => 'ownership_type']);
    }

    /**
     * Gets query for [[Artist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtist()
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getFullName()
    {

        $data = Percentage::find()
            ->select(['artist.name as artist_name', 'ownership.name', 'ownership_type.name as type_name', 'track_to_percentage.percentage'])
            ->from('track_to_percentage')
            ->innerJoin('track', 'track.id = track_to_percentage.track_id')
            ->innerJoin('artist', 'artist.id = track_to_percentage.artist_id')
            ->leftJoin('ownership_type', 'ownership_type.id = track_to_percentage.ownership_type')
            ->leftJoin('ownership', 'ownership.id = ownership_type.ownership_id ')
            ->where(['track_to_percentage.id' => $this->id])
            ->asArray()
            ->one();

        return $data['name'] . ': ' . $data['type_name'];
    }
}