<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_balance".
 *
 * @property int $balance_id
 * @property int $invoice_id
 * @property int $user_id
 * @property int $label_id
 * @property int $artist_id
 * @property int $track_id
 * @property int $currency_id
 * @property float $all_sum
 * @property float $percentage
 * @property float $amount
 * @property bool $is_pay
 * @property string $date_added
 * @property string $date_pay
 * @property string $last_update
 *
 * @property Currency $currency
 * @property Invoice $invoice
 * @property Track $track
 * @property Artist $artist
 * @property SubLabel $label
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
            [['invoice_id', 'user_id', 'currency_id', 'all_sum', 'percentage', 'amount'], 'required'],
            [['invoice_id', 'user_id', 'label_id', 'artist_id', 'track_id', 'currency_id', 'is_pay'], 'integer'],
            [['all_sum', 'percentage', 'amount'], 'number'],
            [['date_added', 'date_pay', 'last_update'], 'safe'],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['track_id'], 'exist', 'skipOnError' => true, 'targetClass' => Track::class, 'targetAttribute' => ['track_id' => 'id']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'invoice_id']],
            [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'balance_id' => '#',
            'invoice_id' => 'Інвойс',
            'user_id' => 'User ID',
            'label_id' => 'Лейбл',
            'artist_id' => 'Артист',
            'track_id' => 'Трек',
            'currency_id' => 'Валюта',
            'all_sum' => 'Доля лейбла',
            'percentage' => 'Відсоток',
            'amount' => 'Сума бонусу',
            'is_pay' => 'Сплачено',
            'date_added' => 'Нараховано',
            'date_pay' => 'Дата сплати',
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
     * Gets query for [[Artist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtist()
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }
    
    public function getLabel()
    {
        return $this->hasOne(SubLabel::class, ['id' => 'label_id']);
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
    
    public static function add(array $data): bool
    {
        $userBalance = new self();
        $userBalance->invoice_id = $data['invoice_id'];
        $userBalance->currency_id = $data['currency_id'];
        $userBalance->all_sum = $data['all_sum'];
        $userBalance->percentage = $data['percentage'];
        $userBalance->user_id = $data['user_id'];
        $userBalance->amount = $data['amount'];//round($sumLabel * ($user->percentage / 100), 3);
        
        if (!empty($data['label_id'])) {
            $userBalance->label_id = $data['label_id'];
        }
        
        if (!empty($data['artist_id'])) {
            $userBalance->artist_id = $data['artist_id'];
        }
        
        if (!empty($data['track_id'])) {
            $userBalance->track_id = $data['track_id'];
        }
        
        if (!$userBalance->save()) {
            var_dump($userBalance->getErrors());
            return false;
        }
        
        return true;
    }
}