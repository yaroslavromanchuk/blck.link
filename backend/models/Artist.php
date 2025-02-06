<?php

namespace backend\models;

use common\models\SubLabel;
use Yii;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property int $artist_type_id
 * @property int $label_id
 * @property string $name
 * @property string $full_name
 * @property string $contract
 * @property string $tov_name
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
* @property string $date_last_payment
* @property int $last_payment_invoice
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
            [['label_id', 'active', 'admin_id', 'percentage', 'telegram_id', 'artist_type_id', 'last_payment_invoice'], 'integer'],
            [['deposit', 'deposit_1'], 'number'],
            [['name'], 'string', 'max' => 150],
            [['full_name', 'contract', 'tov_name'], 'string', 'max' => 100],
            [['percentage'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true,  'message' => Yii::t('app', 'Max 100%')],
            ['name', 'unique', 'targetClass' => Artist::class, 'message' => Yii::t('app', 'Артист з цим псевдонімом вже існує!')],
            [['logo', 'facebook', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['file'], 'image', 'extensions' => 'png, jpg, jpeg'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
            [['date_last_payment'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
	{
        return [
            'id' => Yii::t('app', '№'),
            'artist_type_id' => Yii::t('app', 'Тип'),
            'label_id' => Yii::t('app', 'Лейбл'),
            'name' => Yii::t('app', 'Псевдонім'),
            'full_name' => Yii::t('app', 'ПІБ'),
            'contract' => Yii::t('app', 'Договір'),
            'tov_name' => Yii::t('app', 'Назва ТОВ'),
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
            'last_payment_invoice' => Yii::t('app', 'Останій інвойс на виплата'),
            'date_last_payment' => Yii::t('app', 'Остання виплата'),
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

    public function getType(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ArtistType::class, ['type_id' => 'artist_type_id']);
    }

    /**
     * Gets query for [SubLabel]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLabel(): \yii\db\ActiveQuery
    {
        return $this->hasOne(SubLabel::class, ['id' => 'label_id']);
    }

    public function getLogo(): string
	{
        return Yii::getAlias('@site').'/images/artist/'.$this->logo;
    }

    public function saveBalance(int $quarter, int $currency_id): void
    {
            $balance = Yii::$app->db->createCommand("
                        SELECT sum(ii.amount) as dep 
                        FROM `invoice_items` ii 
                            LEFT JOIN `invoice` i ON i.invoice_id = ii.invoice_id 
                        WHERE i.currency_id = :currency_id
                          and ii.artist_id = :artist_id 
                          and i.quarter < :quarter 
                          and i.year <= :year")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', 3)
            ->bindValue(':currency_id', $currency_id)
            ->bindValue(':year', date('Y'))
            ->queryOne();

        $balance = $balance['dep'] ?? 0;

        if (is_null($balance)) {
            $balance = 0;
        }

        $all = Yii::$app->db->createCommand(
            "SELECT i.invoice_type,
                        it.invoice_type_name,
                        sum(ii.amount) as amount
                 FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id
                    left join invoice_type it ON it.invoice_type_id = i.invoice_type
                 WHERE i.invoice_status_id = 2 
                    and i.invoice_type in (1, 3, 4, 5)
                    and i.currency_id =:currency_id
                    and i.quarter =:quarter
                    and i.year = :year
                    and ii.artist_id =:artist_id
                 group BY ii.invoice_id")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', 3)
            ->bindValue(':year', date('Y'))
            ->bindValue(':currency_id', $currency_id)
            ->queryAll();

        $qq = [
            1 => 0, // нарахування
            3 => 0,  // витрати
            4 => 0,  // аванс
            5 => 0,  // баланс
        ];

        $mapping = [
            3 => 2,
            4 => 3,
            5 => 12,
        ];

        foreach ($all as $item) {
            $qq[$item['invoice_type']] += $item['amount'];
        }

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
            'artist_id' => $this->id,
            'currency_id' => $currency_id,
            'quarter' => $quarter,
            'type_id' => 1, //баланс
            'sum' => $balance,
        ])->execute();

        $temp_sum = $balance;

        foreach ($qq as $key => $value) {
            if (isset($mapping[$key])) {
                $temp_sum += $value;
                Yii::$app->db->createCommand()
                    ->insert(ArtistLog::tableName(), [
                    'artist_id' => $this->id,
                    'currency_id' => $currency_id,
                    'quarter' => $quarter,
                    'type_id' => $mapping[$key], // витрати, аванси, баланси
                    'sum' => $value,
                ])->execute();
            }
        }

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 4, // Всього баланс
                'sum' => $temp_sum,
            ])->execute();

        $all_2 = Yii::$app->db->createCommand(
            "SELECT ii2.artist_id, ii2.from_artist_id, ii2.amount, inv.avtor, if(inv.t_a_id=ii2.artist_id, 1 ,0) as avtor2
                    FROM `invoice_items` ii2
                    INNER JOIN (
                        SELECT i.invoice_id, ii.isrc, if(t.artist_id = ii.artist_id, 1, 0) as avtor, t.artist_id as t_a_id
                            FROM `invoice_items` ii 
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                            INNER JOIN track t ON t.isrc = ii.isrc
                    	LEFT JOIN artist a ON a.id = ii.artist_id
                            WHERE i.invoice_status_id = 2 
                                and i.invoice_type = 1 
                                and i.currency_id =:currency_id
                            and i.quarter =:quarter
                            and ii.artist_id =:artist_id
                    ) as inv ON inv.invoice_id = ii2.invoice_id and inv.isrc = ii2.isrc
            ")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', 3)
            ->bindValue(':currency_id', $currency_id)
            ->queryAll();

        $all_b = $artist_a_b = $artist_f_b = $label_b = $feat_b = 0;

        foreach ($all_2 as $item) {
            if ($item['artist_id'] == $this->id) {
                if ($item['avtor2'] == 1) { // дохід артиста особистий
                    $artist_a_b += $item['amount'];
                } else { // дохід з фітів
                    $artist_f_b += $item['amount'];
                }

                $all_b += $item['amount'];
            } else if ($item['artist_id'] == 0 && $item['from_artist_id'] == $this->id) { // дохід лейбла з артиста
                $label_b += $item['amount'];
                $all_b += $item['amount'];
            } else if ($item['artist_id'] != 0 && $item['avtor2'] == 0) { // дохід артистів на фітах
                $feat_b += $item['amount'];
                $all_b += $item['amount'];
            }
        }

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
            'artist_id' => $this->id,
            'currency_id' => $currency_id,
            'quarter' => $quarter,
            'type_id' => 5, // Загальний дохід за період
            'sum' => $all_b,
        ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 6, // Частка артиста
                'sum' => $artist_a_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 7, // Частка артиста з фітів
                'sum' => $artist_f_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 8, // Частка лейбла
                'sum' => $label_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 9, // Частка артистів на фіті
                'sum' => $feat_b,
            ])->execute();


        $t_sum = round($temp_sum + $artist_a_b + $artist_f_b, 2);
        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 10, // Баланс на кінець періоду
                'sum' => $t_sum,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'type_id' => 11, // Сума
                'sum' => $t_sum > 0 ? $t_sum : 0,
            ])->execute();
    }

    public static function calculationDeposit(?int $artistId = null): array
    {
        $errors = [];

        if (null !== $artistId) {
            $artist = Artist::findOne($artistId);

            $deposits = (new \yii\db\Query())->from(InvoiceItems::tableName())
                ->select('currency_id, SUM(amount) as deposit')
                ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                ->leftJoin(User::tableName(), 'user.id = invoice.user_id')
                ->where([
                    'invoice_items.artist_id' => $artistId,
                    'invoice.invoice_status_id' => 2,
                    'user.label_id' => Yii::$app->user->identity->label_id
                ])
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
                ->where(['label_id' => Yii::$app->user->identity->label_id])
                ->one();

                $uah = (float) $deposit['uah'] ?? 0; // UAH
                $euro = (float) $deposit['euro'] ?? 0; // EURO

            $euro_1 = $uah_1 = 0;

            $amountAll = InvoiceItems::find()
                ->select(['currency_id', 'SUM(amount) as deposit'])
                ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                ->leftJoin(User::tableName(), 'user.id = invoice.user_id')
                ->where([
                    'invoice.invoice_status_id' => 2,
                    'user.label_id' => Yii::$app->user->identity->label_id
                ])
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
                    ->leftJoin(User::tableName(), 'user.id = invoice.user_id')
                    ->where([
                        'invoice.invoice_status_id' => 2,
                        'invoice.currency_id' => 2,
                        'user.label_id' => Yii::$app->user->identity->label_id
                    ])
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
                    ->leftJoin(User::tableName(), 'user.id = invoice.user_id')
                    ->where([
                        'invoice.invoice_status_id' => 2,
                        'invoice.currency_id' => 1,
                        'user.label_id' => Yii::$app->user->identity->label_id
                        ])
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
