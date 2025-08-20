<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoice_log".
 *
 * @property int $log_id
 * @property int $invoice_id
 * @property int $user_id
 * @property int|null $artist_id
 * @property int|null $track_id
 * @property int $log_type_id
 * @property string $date_added
 * @property string $last_update
 *
 * @property Invoice $invoice
 * @property User $user
 */
class InvoiceLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'invoice_id', 'user_id', 'log_type_id'], 'required'],
            [[ 'invoice_id', 'user_id', 'artist_id', 'track_id'], 'integer'],
            //['action', 'string', 'max' => 100],
            [['date_added', 'last_update'], 'safe'],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'invoice_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'invoice_id' => 'Invoice ID',
            'user_id' => 'User ID',
            'artist_id' => 'Artist ID',
            'track_id' => 'Track ID',
            'log_type_id' => 'Тип',
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
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['invoice_id' => 'invoice_id']);
    }

    /**
     * Gets query for [[LogType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogType()
    {
        return $this->hasOne(InvoiceLogType::class, ['log_type_id' => 'log_type_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function add(int $invoice_id, int $log_type_id, ?int $artist_id = null, ?int $track_id = null): bool
    {
        $log = new self();
        $log->invoice_id = $invoice_id;

        $log->user_id = Yii::$app->user->id;
        $log->log_type_id = $log_type_id;

        if (!empty($artist_id)) {
            $log->artist_id = $artist_id;
        }

        if (!empty($track_id)) {
            $log->track_id = $track_id;
        }

        if (!$log->save()) {
            Yii::$app->session->setFlash('error', $log->getErrors());

            return false;
        }

       return true;
    }
}
