<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $logo
 * @property int $active
 * @property string $facebook 
* @property string $vk 
* @property string $twitter 
* @property string $youtube 
* @property string $instagram 
* @property string $telegram 
* @property string $viber 
* @property string $whatsapp 
* @property string $ofsite
* @property int $percentage
* @property double $deposit
* @property double $deposit_1
* @property int $telegram_id
*
* @property Track[] $tracks
*/
class Artist extends \yii\db\ActiveRecord
{
    public const label = 0;

     public $file;
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
	{
        return 'artist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
	{
        return [
            [['name', 'percentage'], 'required'],
            [['active', 'admin_id', 'percentage', 'telegram_id'], 'integer'],
            [['deposit', 'deposit_1'], 'number'],
            [['name'], 'string', 'max' => 150],
            [['percentage'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true,  'message' => Yii::t('app', 'Max 100%')],
            ['name', 'unique', 'targetClass' => Artist::class, 'message' => Yii::t('app', 'Артист з цим ім\'ям вже існує!')],
            [['logo', 'facebook', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['file'], 'image', 'extensions' => 'png, jpg, jpeg'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
	{
        return [
            'id' => Yii::t('app', '№'),
            'name' => Yii::t('app', 'І\'мя'),
            'logo' => Yii::t('app', 'Фото'),
            'phone' => Yii::t('app', 'Телефон'),
            'email' => Yii::t('app', 'Email'),
            'active' => Yii::t('app', 'Активність'),
            'facebook' => Yii::t('app', 'Facebook'),
            'vk' => Yii::t('app', 'Vk'),
            'twitter' => Yii::t('app', 'Twitter'),
            'youtube' => Yii::t('app', 'Youtube'),
            'instagram' => Yii::t('app', 'Instagram'),
            'telegram' => Yii::t('app', 'Telegram'),
            'viber' => Yii::t('app', 'Viber'),
            'whatsapp' => Yii::t('app', 'Whatsapp'),
            'ofsite' => Yii::t('app', 'Оф.Сайт'),
            //'reliz' => Yii::t('app', 'Релизы'),
            'admin_id' => Yii::t('app', 'Створив'),
            'percentage' => Yii::t('app', 'Відсоток %'),
            'file' => Yii::t('app', 'Лого'),
            'deposit' => Yii::t('app', 'Депозит UAH'),
            'deposit_1' => Yii::t('app', 'Депозит EURO'),
            'telegram_id' => Yii::t('app', 'ТелеграмID'),
        ];
    }

    /**
     * Gets query for [[Tracks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTracks(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Track::class, ['artist_id' => 'id']);
    }
    /**
     * Gets query for [Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
    public function getLogo(): string
	{
        return Yii::getAlias('@site').'/images/artist/'.$this->logo;
    }

    public static function calculationDeposit(?int $artistId = null): array
    {
        $errors = [];

        if (null !== $artistId) {
            $artist = Artist::findOne($artistId);

            $deposits = (new \yii\db\Query())->from(InvoiceItems::tableName())
                ->select('currency_id, SUM(amount) as deposit')
                ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                ->where(['artist_id' => $artistId, 'invoice.invoice_status_id' => 2])
                ->groupBy('currency_id')
                ->all();

            $euro = $uah = 0;

            foreach ($deposits as $deposit) {
                if ($deposit['currency_id'] == 2) { // UAH
                    $uah = (float) $deposit['deposit'];
                } else {
                    $euro = (float) $deposit['deposit'];
                }
            }

            if ($uah != $artist->deposit) {
                $errors[$artistId]['deposit'] = [
                    'old' => $artist->deposit,
                    'new' => $uah,
                ];

                $artist->deposit = $uah;
                $artist->save();
            }

            if ($euro != $artist->deposit_1) {
                $errors[$artistId]['deposit_1'] = [
                    'old' => $artist->deposit_1,
                    'new' => $euro,
                ];

                $artist->deposit_1 = $euro;
                $artist->save();
            }
        } else {

            $deposit = (new \yii\db\Query())
                ->from(self::tableName())
                ->select('SUM(deposit) as uah, SUM(deposit_1) as euro')
                //->where(['!=', 'id', 0])
                ->one();

                $uah = (float) $deposit['uah'] ?? 0; // UAH
                $euro = (float) $deposit['euro'] ?? 0; // EURO

            $euro_1 = $uah_1 = 0;

            $amountAll = InvoiceItems::find()
                ->select(['currency_id', 'SUM(amount) as deposit'])
                ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                ->where(['invoice.invoice_status_id' => 2])
                //->andWhere(['!=', 'invoice_items.artist_id', 0])
                ->groupBy(['currency_id'])
                ->asArray()
                ->all();

            foreach ($amountAll as $deposit) {
                if ($deposit['currency_id'] == 2) { // UAH
                    $uah_1 = (float) $deposit['deposit'];
                } else {
                    $euro_1 = (float) $deposit['deposit'];
                }
            }


            if ($uah != $uah_1) {
                $all = (new \yii\db\Query())
                    ->from(InvoiceItems::tableName())
                    ->select('artist_id, SUM(invoice_items.amount) as deposit')
                    ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                    ->where(['invoice.invoice_status_id' => 2, 'invoice.currency_id' => 2])
                    //->andWhere(['!=', 'invoice_items.artist_id', 0])
                    ->groupBy(['artist_id'])
                    ->all();

                foreach ($all as $item) {
                    $artist = Artist::findOne((int)$item['artist_id']);

                    if ($artist->deposit != $item['deposit']) {
                        $errors[$item['artist_id']]['deposit'] = [
                            'old' => $artist->deposit,
                            'new' => (float) $item['deposit'],
                        ];

                        $artist->deposit = (float) $item['deposit'];
                        $artist->save();
                    }
                }
            }

            if ($euro != $euro_1) {
                $all = (new \yii\db\Query())
                    ->from(InvoiceItems::tableName())
                    ->select('artist_id, SUM(invoice_items.amount) as deposit')
                    ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                    ->where(['invoice.invoice_status_id' => 2, 'invoice.currency_id' => 1])
                    //->andWhere(['!=', 'invoice_items.artist_id', 0])
                    ->groupBy(['artist_id'])
                    ->all();

                foreach ($all as $item) {
                    $artist = Artist::findOne((int)$item['artist_id']);

                    if ($artist->deposit_1 != $item['deposit']) {
                        $errors[$item['artist_id']]['deposit_1'] = [
                            'old' => $artist->deposit_1,
                            'new' => (float) $item['deposit'],
                        ];

                        $artist->deposit_1 = (float) $item['deposit'];
                        $artist->save();
                    }
                }
            }
        }

        return $errors;
    }
}
