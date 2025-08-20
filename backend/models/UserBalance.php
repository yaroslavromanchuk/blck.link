<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_balance".
 *
 * @property int $balance_id
 * @property int $invoice_id
 * @property int $user_id
 * @property int $track_id
 * @property int $currency_id
 * @property float $amount
 * @property string $date_added
 * @property string $last_update
 *
 * @property Currency $currency
 * @property Invoice $invoice
 * @property Track $track
 * @property User $user
 */
class UserBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'user_id', 'track_id', 'currency_id', 'amount'], 'required'],
            [['invoice_id', 'user_id', 'track_id', 'currency_id'], 'integer'],
            [['amount'], 'number'],
            [['date_added', 'last_update'], 'safe'],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'invoice_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'balance_id' => 'Balance ID',
            'invoice_id' => 'Invoice ID',
            'user_id' => 'User ID',
            'track_id' => 'Track ID',
            'currency_id' => 'Currency ID',
            'amount' => 'Amount',
            'date_added' => 'Date Added',
            'last_update' => 'Last Update',
        ];
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
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}