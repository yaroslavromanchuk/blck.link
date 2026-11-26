<?php

namespace backend\services;

use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceType;
use Yii;
use yii\db\Query;

/**
 * Конструктор запитів для розрахунку балансу артиста
 *
 * Централізує всі SQL запити для обчислення балансу, доходів, витрат тощо
 * Полегшує оптимізацію та тестування запитів
 */
class ArtistBalanceQueryBuilder
{
    private static function getCurrentPayInvoiceId(int $artistId, int $currencyId, int $quarter, int $year): ?int
    {
        $invoiceId = (new Query())
            ->select('MAX(ip.invoice_id)')
            ->from(Invoice::tableName() . ' ip')
            ->innerJoin(InvoiceItems::tableName() . ' iip', 'iip.invoice_id = ip.invoice_id')
            ->where([
                'ip.invoice_type' => InvoiceType::PAY,
                'ip.invoice_status_id' => [2, 4],
                'ip.currency_id' => $currencyId,
                'ip.quarter' => $quarter,
                'ip.year' => $year,
                'iip.artist_id' => $artistId,
            ])
            ->scalar();

        return $invoiceId !== null ? (int)$invoiceId : null;
    }

    /**
     * Отримати попередній баланс артиста до вказаного кварталу
     */
    public static function getPreviousBalance(int $artistId, int $currencyId, int $quarter, int $year): float
    {
        $currentPayInvoiceId = self::getCurrentPayInvoiceId($artistId, $currencyId, $quarter, $year);
        $paymentCondition = $currentPayInvoiceId === null
            ? ''
            : ' AND ii.payment_invoice_id != :current_pay_invoice_id';

        $cmd = Yii::$app->db->createCommand("
            SELECT COALESCE(SUM(ii.amount), 0) AS dep
            FROM `invoice_items` ii
                INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.currency_id = :currency_id
              AND ii.artist_id = :artist_id
                AND i.invoice_status_id IN (2, 4)
              AND (
                    (i.year < :year OR (i.year = :year AND i.quarter < :quarter))
                  OR (i.year = :year AND i.quarter = :quarter AND i.invoice_type = 3 and ii.payment_invoice_id IS not NULL {$paymentCondition}) -- include payouts for the current quarter
              )
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);

        if ($currentPayInvoiceId !== null) {
            $cmd->bindValue(':current_pay_invoice_id', $currentPayInvoiceId);
        }

        return (float)($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати всі витрати/авансові за період
     */
    public static function getCostForPeriod(int $artistId, int $currencyId, int $quarter, int $year): float
    {
        $currentPayInvoiceId = self::getCurrentPayInvoiceId($artistId, $currencyId, $quarter, $year);

        $query = (new Query())
            ->from(InvoiceItems::tableName() . ' ii')
            ->select('SUM(ii.amount) as amount')
            ->innerJoin(Invoice::tableName() . ' i', 'i.invoice_id = ii.invoice_id')
            ->where([
                'i.invoice_status_id' => 2,
                'i.invoice_type' => 3,
                'i.currency_id' => $currencyId,
                'i.quarter' => $quarter,
                'i.year' => $year,
                'ii.artist_id' => $artistId
            ])
            ->groupBy('i.invoice_type')
            ->indexBy('invoice_type');

        if ($currentPayInvoiceId === null) {
            $query->andWhere(['ii.payment_invoice_id' => null]); // для валют які не виплачуємо
        } else {
            $query->andWhere(['ii.payment_invoice_id' => $currentPayInvoiceId]); // для конкретної виплати
        }

        return (float)($query->scalar() ?? 0);
    }

    /**
     * Отримати основний доход артиста за період (фізичні переслухування його треків)
     *
     * ВИПРАВЛЕНО: Тепер правильно розраховує тільки прямий дохід артиста (ii.artist_id = :artist_id)
     * Не включає дохід лейбу (ii.from_artist_id)
     */
    public static function getArtistDirectIncome(int $artistId, int $currencyId, int $quarter, int $year, int $invoiceType = null): float
    {
        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            LEFT JOIN `track` t ON t.id = ii.track_id
            WHERE i.invoice_status_id = 2
              AND i.invoice_type = :invoice_type
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = :artist_id
              AND ii.from_artist_id IS NULL
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':invoice_type', $invoiceType);

        return (float) ($cmd->queryScalar() ?? 0);
    }


    /**
     * ✅ ПАТЧ: Отримати дохід артиста з фітів (feature income) з LEFT JOIN
     *
     * Розраховує дохід де артист виступає у якості фітчу (не автор)
     * Зміни:
     * - INNER JOIN `track` → LEFT JOIN `track`
     * - Додано обробка коли t.id IS NULL (трек видалено)
     */
    public static function getArtistFeatureIncome(int $artistId, int $currencyId, int $quarter, int $year, ?int $invoiceType = null): float
    {
        $incomeFilter = $invoiceType === null
            ? 'i.invoice_type IN (' . implode(',', InvoiceType::INCOME_TYPES) . ')'
            : 'i.invoice_type = :invoice_type';

        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            LEFT JOIN `track` t ON t.id = ii.track_id
            WHERE i.invoice_status_id = 2
              AND {$incomeFilter}
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = :artist_id
              AND (t.artist_id != ii.artist_id OR t.id IS NULL)
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);

        if ($invoiceType !== null) {
            $cmd->bindValue(':invoice_type', $invoiceType);
        }

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати Partner (Client) за період
     *
     * Partner розраховується як прямий дохід на основі artist.percentage або artist.percentage_distribution
     * залежно від типу агрегатора
     *
     * @param int $partnerId ID Partner артиста
     * @param int $currencyId ID валюти
     * @param int $quarter Квартал
     * @param int $year Рік
     * @return float Загальний дохід для Partner (до розділення на паблішинг/дистрибуцію)
     */
    public static function getPartnerTotalIncome(int $partnerId, int $currencyId, int $quarter, int $year, ?int $invoiceType = null): float
    {
        $incomeFilter = $invoiceType === null
            ? 'i.invoice_type IN (' . implode(',', InvoiceType::INCOME_TYPES) . ')'
            : 'i.invoice_type = :invoice_type';

        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.invoice_status_id = 2
              AND {$incomeFilter}
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = :partner_id
              AND ii.from_artist_id IS NULL
        ")
            ->bindValue(':partner_id', $partnerId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);

        if ($invoiceType !== null) {
            $cmd->bindValue(':invoice_type', $invoiceType);
        }

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати дохід лейбу (label income) від Partner за період
     *
     * Коли Partner отримує дохід, лейбл (контрагент) також отримує свою частку
     * Це записується як: artist_id = 0, from_artist_id = partner_id
     *
     * @param int $partnerId ID Partner артиста
     * @param int $currencyId ID валюти
     * @param int $quarter Квартал
     * @param int $year Рік
     * @return float Дохід лейбу від Partner
     */
    public static function getLabelIncomeFromPartner(int $partnerId, int $currencyId, int $quarter, int $year, ?int $invoiceType = null): float
    {
        $incomeFilter = $invoiceType === null
            ? 'i.invoice_type IN (' . implode(',', InvoiceType::INCOME_TYPES) . ')'
            : 'i.invoice_type = :invoice_type';

        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.invoice_status_id = 2
              AND {$incomeFilter}
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = 0
              AND ii.from_artist_id = :partner_id
        ")
            ->bindValue(':partner_id', $partnerId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);

        if ($invoiceType !== null) {
            $cmd->bindValue(':invoice_type', $invoiceType);
        }

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function getLabelIncomeFromArtist(int $artistId, int $currencyId, int $quarter, int $year, int $invoiceType): float
    {
        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.invoice_status_id = 2
              AND i.invoice_type = :invoice_type
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = 0
              AND ii.from_artist_id = :artist_id
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':invoice_type', $invoiceType);

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати загальний дохід артиста (персональний + феатури)
     *
     * ВИПРАВЛЕНО: Тепер не включає дохід лейбу (ii.from_artist_id)
     * Тільки безпосередньо дохід артиста (ii.artist_id = :artist_id)
     */
    public static function getTotalIncomeForArtist(int $artistId, int $currencyId, int $quarter, int $year, ?int $invoiceType = null): float
    {
        $incomeFilter = $invoiceType === null
            ? 'i.invoice_type IN (' . implode(',', InvoiceType::INCOME_TYPES) . ')'
            : 'i.invoice_type = :invoice_type';

        $cmd = Yii::$app->db->createCommand(" 
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.invoice_status_id = 2
              AND {$incomeFilter}
              AND i.currency_id = :currency_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ii.artist_id = :artist_id
              AND ii.from_artist_id IS NULL
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year);

        if ($invoiceType !== null) {
            $cmd->bindValue(':invoice_type', $invoiceType);
        }

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати загальну суму виплат артисту за період
     */
    public static function getPayoutAmountForPeriod(int $artistId, int $currencyId, int $quarter, int $year): float
    {
        $cmd = Yii::$app->db->createCommand(" 
            SELECT ABS(SUM(ii.amount)) as pay 
            FROM `invoice_items` ii 
                INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id 
                    AND i.invoice_type = 2
                    AND i.invoice_status_id = 2
            WHERE i.currency_id = :currency_id
              AND ii.artist_id = :artist_id 
              AND i.quarter = :quarter 
              AND i.year = :year
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':year', $year);

        return (float) ($cmd->queryScalar() ?? 0);
    }

    /**
     * Отримати аванс на наступний квартал
     */
    public static function getNextQuarterAdvance(int $artistId, int $currencyId, int $currentQuarter, int $currentYear): float
    {
        $nextQuarter = $currentQuarter == 4 ? 1 : $currentQuarter + 1;
        $nextYear = $currentQuarter == 4 ? $currentYear + 1 : $currentYear;

        return (float) Yii::$app->db->createCommand("
            SELECT SUM(ii.amount) as amount
            FROM `invoice_items` ii
            INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
            WHERE i.invoice_status_id = 2
              AND i.invoice_type = 3
              AND i.currency_id = :currency_id
              AND ii.artist_id = :artist_id
              AND i.year = :year
              AND i.quarter = :quarter
        ")
            ->bindValue(':artist_id', $artistId)
            ->bindValue(':currency_id', $currencyId)
            ->bindValue(':quarter', $nextQuarter)
            ->bindValue(':year', $nextYear)
            ->queryScalar() ?? 0;
    }

    /**
     * Отримати депозити артиста по валютах (суми до виплати)
     */
    public static function getDepositsByArtist(int $artistId): array
    {
        return Yii::$app->db->createCommand("
            SELECT 
                ii.artist_id,
                i.currency_id,
                SUM(ii.amount) as deposit
            FROM `invoice_items` ii 
                INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id 
            WHERE i.invoice_status_id IN (2, 4)
              AND ii.artist_id = :artist_id
            GROUP BY i.currency_id
        ")
            ->bindValue(':artist_id', $artistId)
            ->queryAll();
    }
}

