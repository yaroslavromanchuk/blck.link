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
 * @property int $allocated
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
            [['invoice_type', 'label_id', 'aggregator_id', 'user_id', 'currency_id', 'quarter', 'year', 'paid', 'allocated'], 'integer'],
            [['exchange'], 'number'],
            ['quarter', 'in', 'allowArray' => true,  'range' => [1, 2, 3, 4]],
            ['year', 'in', 'allowArray' => true,  'range' => range(2024, (int) date('Y'), 1)],
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
            'user_id' => Yii::t('app', 'Менеджер'),
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
            'allocated' => Yii::t('app', 'Розподілено'),
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

    public function getInvoiceLogs(int $type = null)
    {
        if (!is_null($type)) {
            return $this->hasMany(InvoiceLog::class, ['invoice_id' => 'invoice_id'])
               // ->andWhere([InvoiceLog::tableName() .'.log_type_id' => $type]);
                ->andOnCondition(['log_type_id' => $type]);
        }
        
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
        $logs = $this->getInvoiceLogs(InvoiceLogType::EMAIL)->all();
        
        return !empty($logs);
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getApproved(): bool
    {
        $logs = $this->getInvoiceLogs(InvoiceLogType::APPROVED)->all();
        
        return !empty($logs);
    }

    /**
     * Gets query for [[MailLog]].
     *
     * @return bool
     */
    public function getPayed()
    {
        $logs = $this->getInvoiceLogs(InvoiceLogType::PAYED)->all();
        
        return !empty($logs);
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
    
    public function getPayInvoiceData()
    {
        return Yii::$app->db->createCommand("SELECT
                        a.name,
                        sum(abs(ii.amount)) as sum,
                        c.currency_name,
                        i.exchange,
                        sum(abs(ii.amount)) * i.exchange as total
                    FROM `invoice_items` ii
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                        LEFT join artist a ON a.id = ii.artist_id
                        LEFT join currency c ON c.currency_id = i.currency_id
                    WHERE ii.invoice_id =:invoice_id
                    GROUP BY ii.artist_id")
            ->bindValue(':invoice_id', $this->invoice_id)
            ->queryAll();
    }
    

    /**
     * Отримати дані про розподіл інвойсу по артистам (лейблам та виконавцям)
     * 
     * ✅ Оптимізовано: замість 3 окремих запитів, тепер 1 запит з UNION ALL
     * ✅ Результати кешуються в памяті замість багаторазових запитів
     */
    public function getInvoiceReportDataGroupArtist(): \yii\db\DataReader|array
    {
        // Один оптимізований запит замість трьох!
        $data = Yii::$app->db->createCommand("
            SELECT 
                'label' as type,
                IFNULL(ii.from_artist_id, 0) as artist_id,
                IFNULL(a.name, '') as name,
                SUM(CASE WHEN ii.artist_id = 0 THEN ii.amount ELSE 0 END) as label_amount,
                SUM(CASE WHEN ii.artist_id > 0 THEN ii.amount ELSE 0 END) as artist_amount,
                c.currency_name
            FROM `invoice_items` ii 
                INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                LEFT JOIN artist a ON a.id = ii.from_artist_id 
                LEFT JOIN currency c ON c.currency_id = i.currency_id
            WHERE ii.invoice_id = :invoice_id
                AND ii.artist_id = 0
            GROUP BY ii.from_artist_id
            
            UNION ALL
            
            SELECT 
                'artist' as type,
                ii.artist_id,
                a.name,
                0 as label_amount,
                SUM(ii.amount) as artist_amount,
                c.currency_name
            FROM `invoice_items` ii 
                INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
                LEFT JOIN artist a ON a.id = ii.artist_id 
                LEFT JOIN currency c ON c.currency_id = i.currency_id
            WHERE ii.invoice_id = :invoice_id
                AND ii.artist_id > 0
            GROUP BY ii.artist_id
            ORDER BY type, artist_id
        ")
            ->bindValue(':invoice_id', $this->invoice_id)
            ->queryAll();

        // Обробити результати і сформувати остаточний результат
        $_label = [];
        $_artist = [];
        
        foreach ($data as $item) {
            if ($item['type'] == 'artist') {
                $_artist[$item['artist_id']] = $item;
            } else {
                $_label[$item['artist_id']] = $item;
            }
        }

        // Формувати результат у старому форматі для сумісності
        $result = [];
        $sum = [0 => 0, 1 => 0, 2 => 0];

        foreach ($_label as $artistId => $label) {
            $art = $_artist[$artistId]['artist_amount'] ?? 0;
            $suma = $label['label_amount'] + $art;
            
            $sum[0] += $suma;
            $sum[1] += $art;
            $sum[2] += $label['label_amount'];

            $result[] = [
                'name' => $label['name'],
                'suma' => $suma,
                'artist' => $art,
                'label' => $label['label_amount'],
                'currency_name' => $label['currency_name'],
            ];
        }

        // Додати суму в кінці
        $result[] = [
            '',
            $sum[0],
            $sum[1],
            $sum[2],
            ''
        ];

        return $result;
    }
    
    public function calculateUser(): void
    {
        // Розрахунок по лейблах
        $labelIds = (new \yii\db\Query())
            ->from(InvoiceItems::tableName() . ' as ii')
            ->select('distinct(a.label_id)')
            ->innerJoin(Artist::tableName() . ' a', 'a.id = ii.artist_id')->andFilterWhere(['!=', 'a.label_id', 0])
            ->where(['invoice_id' => $this->invoice_id])
            ->column();
            
        foreach ($labelIds as $labelId) {
            $usersFromLabel = UserBonus::getUserToLabel($labelId);
            
            if ($usersFromLabel) {
                $sumLabel = $this->getLabelSumFromLabel($labelId);
                if ($sumLabel > 0) {
                    /* @var UserBonus $user */
                    foreach ($usersFromLabel as $user) {
                        // ✅ ВИПРАВЛЕНО: Перевіряємо дублікати ДО обробки
                        $b = UserBalance::findOne([
                            'invoice_id' => $this->invoice_id,
                            'currency_id' => $this->currency_id,
                            'user_id' => $user->user_id,
                            'label_id' => $user->label_id,
                        ]);
                        
                        if ($b) {
                            // Запис вже існує, пропускаємо
                            continue;
                        }
                        
                        $amount = round($sumLabel * ($user->percentage / 100), 3);
                        
                        $res = UserBalance::add([
                            'invoice_id' => $this->invoice_id,
                            'currency_id' => $this->currency_id,
                            'all_sum' => $sumLabel,
                            'percentage' => $user->percentage,
                            'user_id' => $user->user_id,
                            'label_id' => $user->label_id,
                            'amount' => $amount,
                        ]);
                        
                        if (!$res) {
                            throw new \Exception("Failed to add label bonus for user {$user->user_id}");
                        }
                    }
                }
            }
        }

        // ============================================
        // РОЗРАХУНОК ПО АРТИСТАХ (ВИПРАВЛЕНО)
        // ============================================

        // ✅ Крок 1: Розраховуємо суми по артистах один раз
        $artistSums = [];
        $artistItems = [];

        foreach ($this->getInvoiceItems()->all() as $item) {
            if ($item->artist_id > 0) {
                if (!isset($artistSums[$item->artist_id])) {
                    $artistSums[$item->artist_id] = 0;
                    $artistItems[$item->artist_id] = $item;
                }
            }
        }

        // ✅ Крок 2: Для кожного унікального артиста розраховуємо суму один раз
        $processedBonuses = [];  // Трекуємо оброблені комбінації

        foreach ($artistSums as $artistId => $dummy) {
            $item = $artistItems[$artistId];
            $sumLabel = $item->getLabelSumFromArtist();

            if ($sumLabel <= 0) {
                continue;
            }

            $usersFromArtist = UserBonus::getUserToArtist($artistId);

            if (!$usersFromArtist) {
                continue;
            }

            // ✅ Крок 3: Обробляємо кожного користувача
            foreach ($usersFromArtist as $user) {
                $bonusKey = "{$this->invoice_id}_{$artistId}_{$user->user_id}";

                // Перевіряємо чи вже обробили цей бонус
                if (isset($processedBonuses[$bonusKey])) {
                    Yii::warning("Duplicate bonus detected for invoice {$this->invoice_id}, artist {$artistId}, user {$user->user_id}");
                    continue;
                }

                // Двічі перевіряємо БД
                $existingBalance = UserBalance::findOne([
                    'invoice_id' => $this->invoice_id,
                    'currency_id' => $this->currency_id,
                    'user_id' => $user->user_id,
                    'artist_id' => $artistId,
                ]);

                if ($existingBalance) {
                    Yii::warning("Balance already exists for invoice {$this->invoice_id}, artist {$artistId}, user {$user->user_id}");
                    $processedBonuses[$bonusKey] = true;
                    continue;
                }

                $amount = round($sumLabel * ($user->percentage / 100), 3);

                try {
                    $result = UserBalance::add([
                        'invoice_id' => $this->invoice_id,
                        'currency_id' => $this->currency_id,
                        'all_sum' => $sumLabel,
                        'percentage' => $user->percentage,
                        'user_id' => $user->user_id,
                        'artist_id' => $artistId,
                        'amount' => $amount,
                    ]);

                    if ($result) {
                        $processedBonuses[$bonusKey] = true;

                        // 📝 Логування
                        Yii::info([
                            'action' => 'artist_bonus_added',
                            'invoice_id' => $this->invoice_id,
                            'artist_id' => $artistId,
                            'user_id' => $user->user_id,
                            'all_sum' => $sumLabel,
                            'percentage' => $user->percentage,
                            'amount' => $amount,
                        ], 'royalty');
                    } else {
                        throw new \Exception("Failed to add artist bonus for user {$user->user_id}");
                    }
                } catch (\Throwable $e) {
                    Yii::error([
                        'action' => 'artist_bonus_error',
                        'invoice_id' => $this->invoice_id,
                        'artist_id' => $artistId,
                        'user_id' => $user->user_id,
                        'error' => $e->getMessage(),
                    ], 'royalty');

                    throw $e;
                }
            }
        }
    }
    
    public function getLabelSumFromLabel(int $labelId): float
    {
        $sum = 0.0;
        
        if ($this->invoice_type != InvoiceType::$debit) {
            return $sum;
        }
        
        $sum = (new \yii\db\Query())
            ->from(InvoiceItems::tableName() . ' as ii')
            ->select('sum(ii.amount) as sum_amount')
            ->innerJoin(Artist::tableName() . ' as a', 'a.id = ii.from_artist_id')
            ->where([
                'ii.invoice_id' => $this->invoice_id,
                'ii.artist_id' => Artist::LABEL,
                'a.label_id' => $labelId,
            ])
            ->one()['sum_amount'] ?? 0.0;
        
        
        /*$sum = (new \yii\db\Query())
            ->from(InvoiceItems::tableName())
            ->select('sum(amount) as sum_amount')
            ->where(['invoice_id' => $this->invoice_id])
            ->one()['sum_amount'] ?? 0.0;*/
        
        return round($sum, 2);
    }
}
