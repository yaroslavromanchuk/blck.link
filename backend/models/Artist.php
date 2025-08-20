<?php

namespace backend\models;

use backend\widgets\DateFormat;
use Yii;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property int $artist_type_id
 * @property bool $records
 * @property int $label_id
 * @property int $admin_id
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
* @property double $deposit_3
* @property string $date_last_payment
* @property int $last_payment_invoice
* @property int $telegram_id
 * @property string $ipn
 * @property int $edrpou
 * @property int $mfo
 * @property string $bank
 * @property string $description
 * @property string $iban
 * @property string $address
 * @property SubLabel $label
 * @property Country $country
*
* @property Track[] $tracks
*/
class Artist extends \yii\db\ActiveRecord
{
    public const LABEL = 0;

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
            [['name', 'percentage', 'label_id', 'artist_type_id'], 'required'],
            [['label_id', 'active', 'admin_id', 'country_id', 'percentage', 'telegram_id', 'artist_type_id', 'last_payment_invoice', 'label_id'], 'integer'],
            [['edrpou', 'mfo', 'records'], 'integer'],
            [['deposit', 'deposit_1', 'deposit_3'], 'number'],
            //['ipn', 'is10NumbersOnly'],
            [['name', 'bank', 'description', 'full_name',], 'string', 'max' => 150],
            [['iban'], 'string', 'length' => 29],
            [['ipn'], 'string', 'length' => 10],
            [['address'], 'string', 'max' => 250],
            [['contract', 'tov_name'], 'string', 'max' => 100],
            [['percentage'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true,  'message' => Yii::t('app', 'Max 100%')],
            [['name'], 'unique', 'targetAttribute' => ['name', 'label_id'], 'targetClass' => self::class, 'message' => Yii::t('app', 'Артист з цим псевдонімом вже існує для вказаного лейбу!')],
            [['logo', 'facebook', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['file'], 'image', 'extensions' => 'png, jpg, jpeg'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
            [['date_last_payment'], 'safe'],
        ];
    }

    public function is10NumbersOnly($attribute)
    {
        if (!preg_match('/^[0-9]{10}$/', $this->$attribute)) {
            $this->addError($attribute, 'Повинен містити 10 цифр.');
        }
    }

    public function isSubLabel()
    {
        return $this->label_id > 0;
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
            'deposit_3' => Yii::t('app', 'Депозит USD'),
            'telegram_id' => Yii::t('app', 'ТелеграмID'),
            'last_payment_invoice' => Yii::t('app', 'Останій інвойс на виплата'),
            'date_last_payment' => Yii::t('app', 'Остання виплата'),
            'ipn' => Yii::t('app', 'РНОКПП'),
            'edrpou' => Yii::t('app', 'Код ЄДРПОУ'),
            'address' => Yii::t('app', 'Місцезнаходження'),
            'iban' => Yii::t('app', 'IBAN'),
            'bank' => Yii::t('app', 'Банк'),
            'mfo' => Yii::t('app', 'МФО'),
            'description' => Yii::t('app', 'Додатково (коментар)'),
            'country_id' => Yii::t('app', 'Країна'),
            'records' => Yii::t('app', 'Рекордс'),
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

    /**
     * Gets query for [Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Country::class, ['country_id' => 'country_id']);
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
        if (!empty($this->logo) && file_exists('/home/atpjwxlx/domains/blck.link/public_html/frontend/web//images/artist/'.$this->logo)) {
            return Yii::getAlias('@site').'/images/artist/'.$this->logo;
        }

        return '';
    }

    public function isSavedBalance(int $quarter, int $currency_id, int $year = null)
    {
        $ctn = Yii::$app->db->createCommand("
                    SELECT count(al.log_id) as ctn 
                    FROM `artist_log` al
                    WHERE al.currency_id = :currency_id
                      and al.artist_id = :artist_id 
                      and al.quarter < :quarter 
                      and al.year <= :year")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':currency_id', $currency_id)
            ->bindValue(':year', $year)
            ->queryOne();

        if (isset($ctn['ctn'])) {
            if ($ctn['ctn'] == 12) {
                return true;
            } else {
                Yii::$app->db->createCommand()
                    ->delete(ArtistLog::tableName(), [
                        'artist_id' => $this->id,
                        'currency_id'=> $currency_id,
                        'quarter' => $quarter,
                        'year' => $year,
                    ])->execute();
            }
        }

        return false;
    }

    public function saveBalance(int $quarter, int $currency_id, int $year): void
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
        ->bindValue(':quarter', $quarter)
        ->bindValue(':currency_id', $currency_id)
        ->bindValue(':year', $year)
        ->queryOne();

        $balance = $balance['dep'] ?? 0;

        if (is_null($balance)) {
            $balance = 0;
        }

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 1, //баланс
                'sum' => $balance,
            ])->execute();

        $temp_sum = $balance;

        $all = Yii::$app->db->createCommand(
            "SELECT i.invoice_type,
                        it.invoice_type_name,
                        sum(ii.amount) as amount
                 FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id
                    left join invoice_type it ON it.invoice_type_id = i.invoice_type
                 WHERE i.invoice_status_id  in (2, 4)
                    and i.invoice_type in (1, 3, 4, 5)
                    and i.currency_id =:currency_id
                    and i.quarter =:quarter
                    and i.year = :year
                    and ii.artist_id =:artist_id
                 group BY ii.invoice_id")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
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

        foreach ($qq as $key => $value) {
            if (isset($mapping[$key])) {
                $temp_sum += $value;
                Yii::$app->db->createCommand()
                    ->insert(ArtistLog::tableName(), [
                    'artist_id' => $this->id,
                    'currency_id' => $currency_id,
                    'quarter' => $quarter,
                    'year' => $year,
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
                'year' => $year,
                'type_id' => 4, // Всього баланс
                'sum' => $temp_sum,
            ])->execute();

        // дохід артиста за квартал
        $all_2 = Yii::$app->db->createCommand(
            "SELECT ii2.artist_id, ii2.from_artist_id, ii2.amount, inv.avtor, if(inv.t_a_id=ii2.artist_id, 1 ,0) as avtor2
                    FROM `invoice_items` ii2
                    INNER JOIN (
                        SELECT i.invoice_id, ii.isrc, if(t.artist_id = ii.artist_id, 1, 0) as avtor, t.artist_id as t_a_id
                            FROM `invoice_items` ii 
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                            INNER JOIN track t ON t.isrc = ii.isrc
                    	LEFT JOIN artist a ON a.id = ii.artist_id
                            WHERE i.invoice_status_id in (2, 4) 
                                and i.invoice_type = 1 
                                and i.currency_id =:currency_id
                            and i.quarter =:quarter
                            and i.year =:year
                            and ii.artist_id =:artist_id
                    ) as inv ON inv.invoice_id = ii2.invoice_id and inv.isrc = ii2.isrc
            ")
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
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
            'year' => $year,
            'type_id' => 5, // Загальний дохід за період
            'sum' => $all_b,
        ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 6, // Частка артиста
                'sum' => $artist_a_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 7, // Частка артиста з фітів
                'sum' => $artist_f_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 8, // Частка лейбла
                'sum' => $label_b,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 9, // Частка артистів на фіті
                'sum' => $feat_b,
            ])->execute();


        $t_sum = round($temp_sum + $artist_a_b + $artist_f_b, 2);
        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 10, // Баланс на кінець періоду
                'sum' => $t_sum,
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert(ArtistLog::tableName(), [
                'artist_id' => $this->id,
                'currency_id' => $currency_id,
                'quarter' => $quarter,
                'year' => $year,
                'type_id' => 11, // Сума
                'sum' => $t_sum > 0 ? $t_sum : 0,
            ])->execute();
    }

    /**
     * @return array|bool
     */
    public function getLastPayInvoice(?int $invoiceId = null, ?int $currency_id = null): array|bool
    {
        $query = (new \yii\db\Query())->from(InvoiceItems::tableName())
            ->select('invoice.invoice_id, invoice.currency_id, invoice.quarter, invoice.year, invoice.date_pay, invoice.date_added, abs(invoice_items.amount) as amount')
            ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
            ->where([
                'invoice_items.artist_id' => $this->id,
                'invoice.invoice_status_id' => 2,
                'invoice.invoice_type' => 2,
            ]);

        if (!is_null($invoiceId)) {
            $query->andFilterWhere(['!=', 'invoice.invoice_id', $invoiceId]);
        }

        if (!is_null($currency_id)) {
            $query->andFilterWhere(['=', 'invoice.currency_id', $currency_id]);
        }

        return $query->orderBy('invoice.invoice_id DESC')
            ->limit(1)
            ->one();
    }

    public static function calculationDeposit(?int $artistId = null): array
    {
        $errors = [];

        if (null !== $artistId) {
            $artist = Artist::findOne($artistId);

            $deposits = (new \yii\db\Query())->from(InvoiceItems::tableName())
                ->select('currency_id, SUM(amount) as deposit')
                ->leftJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                //->leftJoin(User::tableName(), 'user.id = invoice.user_id')
                ->where([
                    'invoice_items.artist_id' => $artistId,
                    'invoice.invoice_status_id' => [2, 4], // 4 - в процесі виплати, 2 - повернений
                   // 'user.label_id' => Yii::$app->user->identity->label_id
                ])
                ->groupBy('currency_id')
                ->all();

            $euro = $uah = $usd = 0;

            foreach ($deposits as $deposit) {
                if ($deposit['currency_id'] == 2) { // UAH
                    $uah = (float) $deposit['deposit'];
                } else if ($deposit['currency_id'] == 3) { // USD
                    $usd = (float) $deposit['deposit'];
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
            if ($usd != $artist->deposit_3) {
                $errors[$artistId]['deposit_3'] = [
                    'old' => $artist->deposit_3,
                    'new' => $uah,
                ];

                $artist->deposit_3 = $usd;
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
            // обнулємо депозити
            Yii::$app->db->createCommand("UPDATE `artist` SET `deposit`=0")->execute();
            // вираховуємо з інвойсів
            Yii::$app->db->createCommand(
                "UPDATE `artist` a 
                        INNER JOIN (
                            SELECT ii.artist_id, SUM(ii.amount) as deposit 
                                FROM invoice_items ii 
                                    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
                                WHERE i.invoice_status_id in (2, 4)
                                    AND i.currency_id =2 # гривні
                                GROUP BY ii.artist_id
                        ) as b ON b.artist_id = a.id
                     SET a.`deposit`= b.deposit "
            )->execute();


            // обнулємо депозити
            Yii::$app->db->createCommand("UPDATE `artist` SET `deposit_3`=0")->execute();
            // вираховуємо з інвойсів
            Yii::$app->db->createCommand(
                "UPDATE `artist` a 
                        INNER JOIN (
                            SELECT ii.artist_id, SUM(ii.amount) as deposit 
                                FROM invoice_items ii 
                                    INNER JOIN invoice i ON i.invoice_id = ii.invoice_id 
                                WHERE i.invoice_status_id in (2, 4)
                                    AND i.currency_id =3 # USD
                                GROUP BY ii.artist_id
                        ) as b ON b.artist_id = a.id
                     SET a.`deposit_3`= b.deposit"
            )->execute();


            Yii::$app->db->createCommand("UPDATE `artist` SET `deposit_1`= 0")->execute();
            // вираховуємо з інвойсів
            Yii::$app->db->createCommand(
                "UPDATE `artist` a 
                        INNER JOIN (
                            SELECT ii.artist_id, SUM(ii.amount) as deposit 
                                FROM invoice_items ii 
                                    INNER JOIN invoice i ON i.invoice_id = ii.invoice_id 
                                WHERE i.invoice_status_id in (2, 4)
                                    AND i.currency_id = 1 # евро
                                GROUP BY ii.artist_id
                        ) as b ON b.artist_id = a.id
                     SET a.`deposit_1`= b.deposit"
            )->execute();
        }

        return $errors;
    }

    public static function getLog(int $artist_id, int $quarter, int $year, int $currency_id, string $currency_name, ?int $invoice_id = null)
    {
        $invoice = null;
        $artist = Artist::findOne($artist_id);

        if (!is_null($invoice_id)) {
            $invoice = Invoice::findOne($invoice_id);
            $lastPay = $artist->getLastPayInvoice($invoice_id, $currency_id);
        }

        $result = [];
        $balance_type = ArtistLogType::find()
            ->where(['active' =>1])
            ->orderBy('sort')
            ->all();

        foreach ($balance_type as $item) {
            $result[$item->log_type_id] = [
                'name' => $item->name,
                'value' => 0,
                'currency_name' => $currency_name,
            ];
        }

        if ($quarter == 1) {
            $quarter_2 = 5;
            $year_2 = ($year-1);
        } else {
            $quarter_2 = $quarter;
            $year_2 = $year;
        }

        $balance = Yii::$app->db->createCommand("
                    SELECT sum(ii.amount) as dep 
                    FROM `invoice_items` ii 
                        LEFT JOIN `invoice` i ON i.invoice_id = ii.invoice_id 
                    WHERE i.currency_id = :currency_id
                      and ii.artist_id = :artist_id  
                      and i.year <= :year  
                      and i.quarter < :quarter  
                     and i.invoice_status_id in (2, 4)
             ")->bindValue(':artist_id', $artist_id)
            ->bindValue(':currency_id', $currency_id)
            ->bindValue(':quarter', $quarter_2)
            ->bindValue(':year', $year_2)
            ->queryOne();

        $balance = $balance['dep'] ?? 0;

        if (is_null($balance)) {
            $balance = 0;
       }

        $result[1]['value'] = $balance;

        $temp_sum = $balance;

        $query = "SELECT i.invoice_type,
                        it.invoice_type_name,
                        sum(ii.amount) as amount
                 FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id
                    left join invoice_type it ON it.invoice_type_id = i.invoice_type
                 WHERE i.invoice_status_id in (2, 4)
                    and i.invoice_type in (1, 3, 4, 5)
                    and i.currency_id =:currency_id
                    AND ii.artist_id =:artist_id";

        if ($invoice !== null) {
            $query .= " AND i.date_added <= :date_invoice AND i.invoice_id != :invoice_id";

            if (!empty($lastPay['date_pay'])) {
                $query .= " AND i.date_added > :date_last_pay";
            }
        } else {
            $query .= " and i.quarter =:quarter and i.year = :year";
        }

        $query .= " group BY ii.invoice_id";

        $all = Yii::$app->db->createCommand($query)
            ->bindValue(':artist_id', $artist_id)
            ->bindValue(':currency_id', $currency_id);

        if ($invoice !== null) {
            $all->bindValue(':date_invoice', $invoice->date_pay)
                ->bindValue(':invoice_id', $invoice->invoice_id);

            if (!empty($lastPay['date_pay'])) {
                $all->bindValue(':date_last_pay', $lastPay['date_pay']);
            }
        } else {
            $all->bindValue(':quarter', $quarter)
                ->bindValue(':year', $year);
        }

        $all = $all->queryAll();

        $qq = [
            1 => 0, // нарахування
            3 => 0,  // витрати
            4 => 0,  // аванс
            5 => 0,  // баланс
        ];

        $mapping = [
            3 => 2, // Витрати
            4 => 3, // Аванс
            5 => 12, // Баланс
        ];

        foreach ($all as $item) {
            $qq[$item['invoice_type']] += $item['amount'];
        }

        foreach ($qq as $key => $value) {
            if (isset($mapping[$key])) {
                $temp_sum += $value;
                $result[$mapping[$key]]['value'] = $value;
            }
        }

        $q2 = "SELECT ii2.artist_id, ii2.from_artist_id, ii2.amount, inv.avtor, if(inv.t_a_id=ii2.artist_id, 1 ,0) as avtor2
                    FROM `invoice_items` ii2
                    INNER JOIN (
                        SELECT i.invoice_id, ii.isrc, if(t.artist_id = ii.artist_id, 1, 0) as avtor, t.artist_id as t_a_id
                            FROM `invoice_items` ii 
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                            INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii.isrc, '-', '')
                    	LEFT JOIN artist a ON a.id = ii.artist_id
                            WHERE i.invoice_status_id in (2, 4) 
                                and i.invoice_type = 1 
                                and i.currency_id =:currency_id
                                and ii.artist_id =:artist_id";

        if ($invoice !== null) {
            $q2 .= " AND i.date_added <= :date_invoice";

            if (!empty($lastPay['date_pay'])) {
                $q2 .= " AND i.date_added > :date_last_pay";
            }

        } else {
            $q2 .= " AND i.quarter = :quarter and i.year = :year";
        }

        $q2 .= ") as inv ON inv.invoice_id = ii2.invoice_id and REPLACE(inv.isrc, '-', '') = REPLACE(ii2.isrc, '-', '') ";

        // дохід артиста за квартал
        $all_2 = Yii::$app->db->createCommand($q2)
            ->bindValue(':artist_id', $artist_id)
            ->bindValue(':currency_id', $currency_id);

        if ($invoice !== null) {
            $all_2->bindValue(':date_invoice', $invoice->date_pay);

            if (!empty($lastPay['date_pay'])) {
                $all_2->bindValue(':date_last_pay', $lastPay['date_pay']);
            }

        } else {
            $all_2->bindValue(':quarter', $quarter)
                ->bindValue(':year', $year);
        }

        $all_2 = $all_2->queryAll();

        $all_b = $artist_a_b = $artist_f_b = $label_b = $feat_b = 0;

        foreach ($all_2 as $item) {
            if ($item['artist_id'] == $artist_id) {
                if ($item['avtor2'] == 1) { // дохід артиста особистий
                    $artist_a_b += $item['amount'];
                } else { // дохід з фітів
                    $artist_f_b += $item['amount'];
                }

               // $all_b += $item['amount'];
            } else if ($item['artist_id'] == 0 && $item['from_artist_id'] == $artist_id) { // дохід лейбла з артиста
                $label_b += $item['amount'];
               // $all_b += $item['amount'];
            } else if ($item['artist_id'] != 0 ) { // дохід артистів на фітах
                $feat_b += $item['amount'];
               // $all_b += $item['amount'];
            } else if ($item['from_artist_id'] != $artist_id) { // + частка лейба від артистів на фітах
                $feat_b += $item['amount'];
            }

            $all_b += $item['amount'];
        }

        $result[5]['value'] = $all_b; // Загальний дохід за період
        $result[6]['value'] = $artist_a_b; // Частка артиста
        $result[7]['value'] = $artist_f_b; // Частка артиста з фітів
        $result[8]['value'] = $label_b; // Частка лейбла
        $result[9]['value'] = $feat_b; // Частка артистів на фіті

        $t_sum = round($temp_sum + $artist_a_b + $artist_f_b, 2);

        $result[10]['value'] = $t_sum; // Баланс за період

        $query = "
                    SELECT abs(sum(ii.amount)) as pay 
                    FROM `invoice_items` ii 
                        INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id 
                            and i.invoice_type = 2 
                            and i.invoice_status_id in (2, 4)
                    WHERE i.currency_id = :currency_id
                      and ii.artist_id = :artist_id 
                      and i.quarter = :quarter 
                      and i.year = :year";

        if (!is_null($invoice_id)) {
            $query .= " and i.invoice_id != :invoice_id";
        }

        $request = Yii::$app->db->createCommand($query)
            ->bindValue(':artist_id', $artist_id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':currency_id', $currency_id)
            ->bindValue(':year', $year);

        if (!is_null($invoice_id)) {
            $request->bindValue(':invoice_id', $invoice_id);
        }

        $pay = $request->queryOne();

        $pay = $pay['pay'] ?? 0;

        if (is_null($pay)) {
            $pay = 0;
        }

        $result[13]['value'] = $pay; // Сплачено за період
        $pay = round($pay, 2);
        $result[11]['value'] = $t_sum > 0 ? $t_sum - $pay : 0; // Сума до виплати

        return $result;
    }

    public static function getArtistByName(string $name, ?int $label_id = null)
    {
        $conditions = ['name' => trim($name)];

        if (!is_null($label_id)) {
            $conditions['label_id'] = $label_id;
        }

        $artist = self::findOne($conditions);

        if (!is_null($artist)) {
            return $artist;
        }

        $artist = self::find()
            ->andWhere(['like', "artist.name", trim($name)]);

        if (!is_null($label_id)) {
            $artist->andFilterWhere(['=', 'label_id', $label_id]);
        }
        $artist ->one();

        if ($artist instanceof self) {
            return $artist;
        }

        return null;
    }
}
