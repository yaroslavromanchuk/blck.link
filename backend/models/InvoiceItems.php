<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoice_items".
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $track_id
 * @property int $artist_id
 * @property int $from_artist_id
 * @property string|null $isrc
 * @property double $amount
 * @property string $description
 * @property string $date_item
 * @property string $last_update
 *
 * @property Invoice $invoice
 * @property Track $track
 * @property Artist $artist
 */
class InvoiceItems extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'artist_id', 'amount'], 'required'],
            [['invoice_id', 'track_id', 'artist_id', 'from_artist_id'], 'integer'],
            [['amount'], 'number'],
            [['date_item', 'last_update'], 'safe'],
            [['isrc'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'invoice_id']],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'invoice_id' => Yii::t('app', 'Інвойс'),
            'track_id' => Yii::t('app', 'Трек'),
            'artist_id' => Yii::t('app', 'Артист'),
            'from_artist_id' => Yii::t('app', 'Від артиста'),
            'isrc' => Yii::t('app', 'ISRS'),
            //'platform' => Yii::t('app', 'Платформа'),
            'description' => Yii::t('app', 'Коментар'),
            'amount' => Yii::t('app', 'Сума'),
            'date_item' => Yii::t('app', 'Завантажено'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['invoice_id' => 'invoice_id']);
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
     * Gets query for [[Artist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtist()
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }
}
