<?php

namespace backend\models;

use common\models\MailLog;
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
 * @property int|null $percentage
 * @property double $amount
 * @property string $description
 * @property string $date_item
 * @property string $last_update
 *
 * @property Invoice $invoice
 * @property MailLog $mail
 * @property InvoiceLog $log
 * @property Track $track
 * @property Artist $artist
 * @property $note
 */
class InvoiceItems extends \yii\db\ActiveRecord
{
    public null|string  $note = null;
    public null|string $apr = null;
    public null|string  $pay = null;
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
            [['invoice_id', 'artist_id', 'amount', 'date_item'], 'required'],
            [['invoice_id', 'track_id', 'artist_id', 'from_artist_id', 'percentage'], 'integer'],
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
            'date_item' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
            'percentage' => Yii::t('app', 'Відсоток %'),
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
     * Gets query for [[MailLog]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMail()
    {
        return $this->hasMany(MailLog::class, ['invoice_id' => 'invoice_id']);
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLog()
    {
        return $this->hasMany(InvoiceLog::class, ['invoice_id' => 'invoice_id']); //,
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getNotified(): bool
    {
        $logs = $this->getLog()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
            if ($log->artist_id == $this->artist_id && $log->log_type_id == InvoiceLogType::EMAIL) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getApproved(): bool
    {
        $logs = $this->getLog()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
          if ($log->artist_id == $this->artist_id && $log->log_type_id == InvoiceLogType::APPROVED) {
               return true;
          }
        }

        return false;
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getPayed()
    {
        $logs = $this->getLog()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
            if ($log->artist_id == $this->artist_id && $log->log_type_id == InvoiceLogType::PAYED) {
                return true;
            }
        }

        return false;
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
