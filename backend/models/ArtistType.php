<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "artist_type".
 *
 * @property int $type_id
 * @property string $name
 * @property string $date_add
 * @property string $last_update
 *
 * @property Artist[] $artists
 */
class ArtistType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'artist_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'type_id' => Yii::t('app', 'Type ID'),
            'name' => Yii::t('app', 'Name'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }

    /**
     * Gets query for [[Artists]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtists()
    {
        return $this->hasMany(Artist::class, ['artist_type_id' => 'type_id']);
    }
}