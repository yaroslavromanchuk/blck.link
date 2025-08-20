<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "invoices".
 *
 * @property int $invoice_id
 * @property int $label_id
 * @property int $user_id
 * @property int $invoice_type
 * @property int $invoice_status_id
 * @property int $aggregator_id
 * @property int $aggregator_report_id
 * @property int|null $currency_id
 * @property float|null $exchange
 * @property double $total
 * @property int $quarter
 * @property int $year
 * @property string $description
 * @property string $date_pay
 * @property string $period_from
 * @property string $period_to
 * @property int $paid
 * @property string $date_added
 * @property string $last_update
 *
 * @property InvoiceItems[] $invoiceItems
 * @property Aggregator $aggregator
 * @property InvoiceType $invoiceType
 * @property InvoiceLog[] $invoiceLogs
 * @property Currency $currency
 * @property User $user
 * @property SubLabel $label
 * @property AggregatorReport $aggregatorReport
 */
class Invoice extends \yii\db\ActiveRecord
{
    public null|string  $note = null;
    public null|string $apr = null;
    public null|string  $pay = null;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_type', 'currency_id', 'user_id', 'quarter', 'year'], 'required'],
            ['exchange', 'required', 'when' => function($model) {
                return $model->currency_id == 1;
            }, 'whenClient' => "function (attribute, value) {
                return $('#country_id').val() == 1;
            }"],
            [['invoice_type', 'label_id', 'aggregator_id', 'user_id', 'currency_id', 'quarter', 'year', 'paid'], 'integer'],
            [['exchange'], 'number'],
            ['quarter', 'in', 'allowArray' => true,  'range' => [1, 2, 3, 4]],
            ['year', 'in', 'allowArray' => true,  'range' => [2024, 2025, 2026]],
            [['total', 'quarter', 'year'], 'number'],
            [['date_added', 'last_update', 'description', 'date_pay', 'period_from', 'period_to'], 'safe'],
            [['aggregator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aggregator::class, 'targetAttribute' => ['aggregator_id' => 'aggregator_id']],
            [['invoice_type'], 'exist', 'skipOnError' => true, 'targetClass' => InvoiceType::class, 'targetAttribute' => ['invoice_type' => 'invoice_type_id']],
            [['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['invoice_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => InvoiceStatus::class, 'targetAttribute' => ['invoice_status_id' => 'invoice_status_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'invoice_id' => Yii::t('app', '№'),
            'label_id' => Yii::t('app', 'Лейбл'),
            'user_id' => Yii::t('app', 'Додав'),
            'invoice_type' => Yii::t('app', 'Тип інвойсу'),
            'invoice_status_id' => Yii::t('app', 'Статус інвойсу'),
            'aggregator_id' => Yii::t('app', 'Агрегатор'),
            'currency_id' => Yii::t('app', 'Валюта'),
            'exchange' => Yii::t('app', 'Курс'),
            'total' => Yii::t('app', 'Сума'),
            'quarter' => Yii::t('app', 'Квартал'),
            'year' => Yii::t('app', 'Рік'),
            'date_pay' => Yii::t('app', 'Дата виплати'),
            'period_from' => Yii::t('app', 'Період виплати з'),
            'period_to' => Yii::t('app', 'Період виплати по'),
            'date_added' => Yii::t('app', 'Додано'),
            'last_update' => Yii::t('app', 'Оновлено'),
            'ownership_type' => Yii::t('app', 'Тип Ввласності'),
            'description' => Yii::t('app', 'Коментар'),
            'paid' => Yii::t('app', 'Оплату завершено'),
        ];
    }

    /**
     * Gets query for [[InvoiceItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems()
    {
        return $this->hasMany(InvoiceItems::class, ['invoice_id' => 'invoice_id']);
    }

    /**
     * Gets query for [[Aggregator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregator()
    {
        return $this->hasOne(Aggregator::class, ['aggregator_id' => 'aggregator_id']);
    }

    public function getAggregatorReport()
    {
        return $this->hasOne(AggregatorReport::class, ['id' => 'aggregator_report_id']);
    }

    /**
     * Gets query for [[InvoiceType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceType()
    {
        return $this->hasOne(InvoiceType::class, ['invoice_type_id' => 'invoice_type']);
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

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    public function getLabel(): \yii\db\ActiveQuery
    {
        return $this->hasOne(SubLabel::class, ['id' => 'label_id']);
    }
    /**
     * Gets query for [[InvoiceStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceStatus()
    {
        return $this->hasOne(InvoiceStatus::class, ['invoice_status_id' => 'invoice_status_id']);
    }

    public function getInvoiceLogs()
    {
        return $this->hasMany(InvoiceLog::class, ['invoice_id' => 'invoice_id']);
    }

    #region sublabel

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getNotified(): bool
    {
        $logs = $this->getInvoiceLogs()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
            if ($log->log_type_id == InvoiceLogType::EMAIL) {
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
        $logs = $this->getInvoiceLogs()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
            if ($log->log_type_id == InvoiceLogType::APPROVED) {
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
        $logs = $this->getInvoiceLogs()->all();

        if (empty($logs)) {
            return false;
        }

        /* @var InvoiceLog $log */
        foreach ($logs as $log) {
            if ($log->log_type_id == InvoiceLogType::PAYED) {
                return true;
            }
        }

        return false;
    }

    #endregion sublabel

    public function calculate()
    {
        $total = 0;

        foreach ($this->getInvoiceItems()->all() as $item) {
            $total += $item->amount;
        }

        if ($total != $this->total) {
            $this->total = round($total, 4);
            $this->save();
        }
    }

    public function getInvoiceReportDataGroupArtist(): \yii\db\DataReader|array
    {
        return Yii::$app->db->createCommand("SELECT
                        sl.name as label_name,
                        a.name,
                        (sum(ii.amount) + IFNULL(art.amount, 0))  as all_sum,
                        IFNULL(art.amount, 0) as artist_sum,
                        sum(ii.amount) as label_sum,
                        c.currency_name
                    FROM `invoice_items` ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        LEFT join artist a ON a.id = ii.from_artist_id 
                        left JOIN sub_label sl ON sl.id = a.label_id
                        LEFT join currency c ON c.currency_id = i.currency_id
                        LEFT JOIN (
                            SELECT artist_id, sum(amount) as amount
                            FROM `invoice_items` 
                            WHERE invoice_id =:invoice_id
                            and from_artist_id is null
                            GROUP BY artist_id
                        ) as art ON art.artist_id = ii.from_artist_id
                    WHERE ii.invoice_id =:invoice_id
                        AND ii.artist_id = 0
                    GROUP BY ii.from_artist_id")
            ->bindValue(':invoice_id', $this->invoice_id)
            ->queryAll();



        $artist = Yii::$app->db->createCommand(
            "SELECT 
                    ii.artist_id,
                    a.name,
                    sum(ii.amount) as amount,
                    c.currency_name
                    FROM `invoice_items` ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        #LEFT JOIN track t ON t.isrc = ii.isrc 
                        LEFT join artist a ON a.id = ii.artist_id 
                        left join currency c ON c.currency_id = i.currency_id
                    WHERE ii.invoice_id =:invoice_id
                    AND ii.artist_id > 0
                 GROUP BY ii.artist_id 
                 ORDER BY ii.artist_id ASC
            ")
            ->bindValue(':invoice_id', $this->invoice_id)
            ->queryAll();
        $_artist = [];

        foreach ($artist as $item) {
            $_artist[$item['artist_id']] = $item;
        }

        $label = Yii::$app->db->createCommand(
            "SELECT
                    IFNULL(ii.from_artist_id, 0) as artist_id,
                    IFNULL(a.name, '') as name,
                    sum(ii.amount) as amount,
                    c.currency_name
                    FROM `invoice_items` ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        LEFT JOIN artist a ON a.id = ii.from_artist_id 
                        LEFT JOIN currency c ON c.currency_id = i.currency_id
                    WHERE ii.invoice_id =:invoice_id
                    AND ii.artist_id = 0
                 GROUP BY ii.from_artist_id
                 ORDER BY `ii`.`from_artist_id` ASC
            ")
            ->bindValue(':invoice_id', $this->invoice_id)
            ->queryAll();

        $_label = [];

        $sum = [
            0 => 0,
            1 => 0,
            2 => 0
        ];

        foreach ($label as $item) {
            $art = !empty($_artist[$item['artist_id']]['amount']) ? $_artist[$item['artist_id']]['amount'] : 0;

            if (empty($art)) {
                $art = 0;
            }

            $suma = $item['amount'] + $art;
            $sum[0] += $suma;
            $sum[1] += $art;
            $sum[2] += $item['amount'];

            $_label[] = [

                'name' => $item['name'],
                'suma' => $suma,
                'artist' => $art,
                'label' => $item['amount'],
                'currency_name' => $item['currency_name'],
            ];
        }

        $_label[] = [
            '',
            $sum[0],
            $sum[1],
            $sum[2],
            ''
        ];

        return $_label;
    }
}
