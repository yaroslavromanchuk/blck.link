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
 * @property float|null $artist_percentage
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
            [['amount', 'artist_percentage'], 'number'],
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
            'artist_percentage' => Yii::t('app', 'Відсоток з фітами'),
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
    
    public function getLabelSumFromArtist(): float
    {
        $sum = 0.0;
        
        if ($this->invoice->invoice_type != InvoiceType::$debit) {
            return $sum;
        }
       /*
        $invoicesIds = (new \yii\db\Query())
            ->from(InvoiceItems::tableName())
            ->select('distinct(invoice_items.invoice_id)')
            ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
            ->andFilterWhere([
                'invoice.invoice_status_id' => InvoiceStatus::Calculated,
                'invoice.invoice_type' => InvoiceType::$debit,
                'invoice.currency_id ' => $this->invoice->currency_id,
                ])
            ->where([
                'invoice_items.payment_invoice_id' => $this->invoice_id,
                'invoice_items.artist_id' => $this->artist_id,
            ])->all();*/
        
        $sum = (new \yii\db\Query())
                ->from(InvoiceItems::tableName())
                ->select('sum(amount) as sum_amount')
                ->where(['invoice_id' => $this->invoice_id])
                ->andWhere(['from_artist_id' => $this->artist_id, 'artist_id' => Artist::LABEL])
                ->one()['sum_amount'] ?? 0.0;

        return round($sum, 2);
    }
}
