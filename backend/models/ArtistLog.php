<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "artist_log".
 *
 * @property int $log_id
 * @property int $artist_id
 * @property int $currency_id
 * @property int $type_id
 * @property float $sum
 * @property int $quarter
 * @property int $year
 * @property string $date_added
 * @property string $last_update
 *
 * @property Artist $artist
 * @property Currency $currency
 * @property ArtistLogType $type
 */
class ArtistLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'artist_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['artist_id', 'currency_id', 'type_id', 'sum', 'quarter', 'year'], 'required'],
            [['artist_id', 'currency_id', 'type_id', 'quarter', 'year'], 'integer'],
            [['sum'], 'number'],
            [['date_added', 'last_update'], 'safe'],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArtistLogType::class, 'targetAttribute' => ['type_id' => 'log_type_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'artist_id' => 'Artist ID',
            'currency_id' => 'Currency ID',
            'type_id' => 'Type ID',
            'sum' => 'Sum',
            'quarter' => 'Квартал',
            'year' => 'Рік',
            'date_added' => 'Date Added',
            'last_update' => 'Last Update',
        ];
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

    /**
     * Gets query for [[Currency]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
    }

    /**
     * Gets query for [[Type]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(ArtistLogType::class, ['log_type_id' => 'type_id']);
    }
}