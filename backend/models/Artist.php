<?php

namespace backend\models;

use backend\widgets\DateFormat;
use common\models\MailLog;
use Yii;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property int $type_id
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
* @property int $percentage_distribution
* @property double $deposit
* @property double $deposit_1
* @property double $deposit_3
* @property string $date_last_payment
* @property int $last_payment_invoice
* @property int $telegram_id
 * @property string $telegram_code
 * @property string $ipn
 * @property int $edrpou
 * @property int $mfo
 * @property string $bank
 * @property string $description
 * @property string $iban
 * @property string $address
 * @property int $country_id
 * @property int $notify
 * @property SubLabel $label
 * @property Country $country
 * @property Track[] $tracks
 * @property User $admin
 * @property ArtistType $artistType
 * @property ClientType $clientType
 * @property MailLog[] $invoiceLogs
*/
class Artist extends \yii\db\ActiveRecord
{
    public const LABEL = 0;

     public $file;
    public ?int $tracks_count = 0;
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
            [['name', 'percentage',  'type_id', 'label_id', 'artist_type_id'], 'required'],
            [['type_id', 'label_id', 'active', 'admin_id', 'country_id', 'percentage', 'percentage_distribution', 'telegram_id', 'artist_type_id', 'last_payment_invoice', 'label_id', 'notify'], 'integer'],
            [['edrpou', 'mfo', 'records'], 'integer'],
            [['deposit', 'deposit_1', 'deposit_3'], 'number'],
            //['ipn', 'is10NumbersOnly'],
            [['name', 'bank', 'description', 'full_name',], 'string', 'max' => 150],
            [['iban'], 'validateUaIban'],
            [['telegram_code'], 'string', 'length' => 10],
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

    public function validateUaIban($attribute)
    {
        $iban = strtoupper(trim($this->$attribute));
        $iban = str_replace(' ', '', $iban);

        // ✅ має починатися з UA
        if (!str_starts_with($iban, 'UA')) {
            $this->addError($attribute, 'Рахунок має починатися з UA');
            return;
        }

        // ✅ точна довжина
        if (strlen($iban) !== 29) {
            $this->addError($attribute, 'Невірна довжина рахунку');
            return;
        }

        // ✅ тільки латиниця і цифри
        if (!preg_match('/^[A-Z0-9]+$/', $iban)) {
            $this->addError($attribute, 'Недопустимі символи');
            return;
        }

        // ✅ checksum (MOD97)
        if (!$this->validateIbanChecksum($iban)) {
            $this->addError($attribute, 'Невірний рахунок');
        }
    }

    private function validateIbanChecksum($iban): bool
    {
        // перенос перших 4 символів
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            if (ctype_alpha($char)) {
                $numeric .= ord($char) - 55;
            } else {
                $numeric .= $char;
            }
        }

        return $this->mod97($numeric) === 1;
    }

    private function mod97($number): int
    {
        $checksum = 0;

        foreach (str_split($number, 7) as $part) {
            $checksum = (int)($checksum . $part) % 97;
        }

        return $checksum;
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
    
    public function isClient(): bool
    {
        return $this->type_id == 2;
    }
    
    public function isArtist(): bool
    {
        return $this->type_id == 1;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
	{
        return [
            'id' => Yii::t('app', '№'),
            'type_id' => Yii::t('app', 'Тип'),
            'artist_type_id' => Yii::t('app', 'Тип суб\'єкта'),
            'label_id' => Yii::t('app', 'Лейбл'),
            'name' => Yii::t('app', 'Контрагент'),
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
            'percentage' => Yii::t('app', 'Публішинг %'),
            'percentage_distribution' => Yii::t('app', 'Дистрибуція %'),
            'file' => Yii::t('app', 'Лого'),
            'deposit' => Yii::t('app', 'UAH'),
            'deposit_1' => Yii::t('app', 'EURO'),
            'deposit_3' => Yii::t('app', 'USD'),
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
            'notify' => Yii::t('app', 'Повідомляти'),
            'notified' => Yii::t('app', 'Повідомлено'),
            'telegram_code' => Yii::t('app', 'ТГ код'),
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

    public function getArtistType(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ArtistType::class, ['type_id' => 'artist_type_id']);
    }
    
    public function getClientType(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ClientType::class, ['type_id' => 'type_id']);
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
        if (!empty($this->logo) && file_exists('/home/atpjwxlx/domains/blck.link/public_html/frontend/web/images/artist/'.$this->logo)) {
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

    /**
     * Розрахувати й збережити баланс артиста за період
     *
     * Використовує новий ArtistBalanceService для централізованих розрахунків
     * @deprecated Розрахунки тепер виконуються в ArtistBalanceService
     */
    public function saveBalance(int $quarter, int $currency_id, int $year): void
    {
        $service = new \backend\services\ArtistBalanceService();
        $service->saveBalance($this->id, $quarter, $year, $currency_id);
    }

    /**
     * @return array|bool
     */
    public function getLastPayInvoice(int $currency_id, ?int $invoiceId = null): array|bool
    {
        $query = (new \yii\db\Query())
            ->from(InvoiceItems::tableName())
            ->select('invoice.invoice_id, invoice.currency_id, invoice.quarter, invoice.year, invoice.date_pay, invoice.date_added, abs(invoice_items.amount) as amount')
            ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
            ->where([
                'invoice_items.artist_id' => $this->id,
                'invoice.invoice_status_id' => 2,
                'invoice.invoice_type' => 2,
				'invoice.currency_id' => $currency_id,
            ]);

        if (!is_null($invoiceId)) {
            $query->andFilterWhere(['!=', 'invoice.invoice_id', $invoiceId]);
        }
		
        return $query->orderBy(['invoice.invoice_id' =>  SORT_DESC])
            ->limit(1)
            ->one();
    }

    /**
     * Розрахувати й оновити депозити артиста
     *
     * Використовує новий ArtistBalanceService для централізованих розрахунків
     * @deprecated Розрахунки тепер виконуються в ArtistBalanceService::updateArtistDeposits()
     */
    public static function calculationDeposit(?int $artistId = null): array
    {
        return \backend\services\ArtistBalanceService::updateArtistDeposits($artistId);
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
    
    public function getInvoiceLogs(?string $content = null)
    {
        $query = $this->hasMany(MailLog::class, ['artist_id' => 'id']);

        if (!is_null($content)) {
            $query->andOnCondition(['content' => $content]);
        }

        return $query->all();
    }
    
    public function hasNotified()
    {
        $lastInvoice = (new \yii\db\Query())
            ->from(InvoiceItems::tableName())
            ->select('invoice.quarter,
               invoice.year
            ')
            ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
            ->where([
                'invoice.invoice_status_id' => [2, 4],
                'invoice.invoice_type' => 2,
            ])->orderBy(['invoice.year' => SORT_DESC, 'invoice.quarter' =>  SORT_DESC])
            ->limit(1)
            ->one();
        
        $quarter = $lastInvoice['quarter'] ?? null;
        $year = $lastInvoice['year'] ?? null;
        
        $invoices = (new \yii\db\Query())
            ->from(Invoice::tableName())
            ->select('invoice.invoice_id')
            ->where([
                'invoice.invoice_status_id' => [2, 4],
                'invoice.invoice_type' => 2,
                'invoice.quarter' => $quarter,
                'invoice.year' => $year,
                'invoice.label_id' => $this->label_id,
            ])->all();
        
        $ids = [];
        foreach ($invoices as $invoice) {
            $ids[] = $invoice['invoice_id'];
        }
        
        $count = (new \yii\db\Query())
            ->from(InvoiceLog::tableName())
            ->andFilterWhere(['in','invoice_log.invoice_id', $ids])
            ->andFilterWhere(['invoice_log.artist_id' => $this->id])
            ->count();
        
        if ($count > 0 ) {
            return true;
        }
        
        $logs = $this->getInvoiceLogs('Balance Notification');
        $logs = array_filter($logs, function($log) {
            return date('m-Y',strtotime($log->date_added)) == date('m-Y');
        });
        
        return count($logs) > 0;
    }
    
    public function getUserBalances(): \yii\db\ActiveQuery
    {
        return $this->hasMany(UserBalance::class, ['artist_id' => 'id']);
    }
    
    public function getUserToArtist(): array
    {
        return $this->hasMany(UserBonus::class, ['artist_id' => 'id'])->all();
    }
    
    /**
     * отримати доп. дохід артиста за період
     * $invoiceId - для виплат, якщо null - то для мінусових артистів
     */
    public function getIncome(int $quarter, int $year, int $invoiceId = null): array
    {
        $query = "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name
                    FROM `invoice_items` ii
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        LEFT JOIN artist a ON a.id = ii.artist_id
                        LEFT JOIN track t ON t.id = ii.track_id
                        LEFT JOIN currency c ON c.currency_id = i.currency_id
                        left join invoice_type it ON it.invoice_type_id = i.invoice_type
                    WHERE i.invoice_status_id in (2, 4)
                      and i.invoice_type = 5"; // #баланс

                if ($invoiceId !== null) {
                    $query .= " AND ii.payment_invoice_id = {$invoiceId}";
                } else {
                    $query .= " AND ii.payment_invoice_id IS NULL";
                }

               $query .=" and ii.artist_id =:artist_id
                    and i.quarter = :quarter
                    and i.year = :year
                    ORDER BY ii.date_item, i.currency_id
            ";

       return Yii::$app->db->createCommand($query)
           ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->queryAll();
    }
    
    /**
     * отримати витрати артиста за період
     */
    public function getCosts(int $quarter, int $year, ?int $currency_id = null): array
    {
        // витрати за період
        $sql = "SELECT it.invoice_type_name,
                       ii.date_item,
                       a.name as a_name,
                       t.name as t_name,
                       ii.description,
                       ii.amount,
                       c.currency_name
                    FROM `invoice_items` ii
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        INNER JOIN currency c ON c.currency_id = i.currency_id
                        INNER join invoice_type it ON it.invoice_type_id = i.invoice_type
                        LEFT JOIN artist a ON a.id = ii.artist_id
                        LEFT JOIN track t ON t.id = ii.track_id
                        WHERE i.invoice_status_id in (2, 4)
                          AND i.invoice_type IN (3, 4) #витрати і аванси
                          AND ii.artist_id =:artist_id
                          AND i.quarter = :quarter
                          AND i.year = :year";
        
        if (!is_null($currency_id)) {
            $sql .= " AND i.currency_id =:currency_id";
        }
        
        $sql .= " ORDER BY ii.date_item, i.currency_id";
        
        $sql =  Yii::$app->db->createCommand($sql)
            ->bindValue(':artist_id', $this->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);
        
        if (!is_null($currency_id)) {
            $sql->bindValue(':currency_id', $currency_id);
        }
        
        return $sql->queryAll();
    }
    
    public function getDep(int $currency_id): float
    {
        return match ($currency_id) {
            1  => $this->deposit_1, // EURO
            2  => $this->deposit, // UAH
            3 => $this->deposit_3, // USD
            default  => 0.0,
        };
    }

    public function saveLog(array $currentData, array $newData): void
    {
        if (array_diff($currentData, $newData)) {
            Yii::$app->db->createCommand()
                ->insert('artist_logging', [
                    'admin_id' => Yii::$app->user->id,
                    'artist_id' => $this->id,
                    'old_data' => serialize($currentData),
                    'new_data' => serialize($newData),
                ])->execute();
        }
    }

    /**
     * Отримати розраховані дані балансу артиста за період
     *
     * Використовує новий ArtistBalanceService для централізованих розрахунків
     * Повертає дані у форматі, сумісному з попередньою версією
     *
     * @deprecated Використовуйте ArtistBalanceService::getBalanceForReport()
     */
    public static function getLog(int $artist_id, int $quarter, int $year, int $currency_id, string $currency_name, ?int $invoice_id = null)
    {
        $service = new \backend\services\ArtistBalanceService();
        return $service->getBalanceForReport($artist_id, $quarter, $year, $currency_id, $currency_name, $invoice_id);
    }
}
