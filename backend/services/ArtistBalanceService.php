<?php

namespace backend\services;

use backend\models\Artist;
use backend\models\ArtistLog;
use backend\models\ArtistLogType;
use backend\models\Currency;
use backend\models\InvoiceType;
use Yii;

/**
 * Сервіс для розрахунку та збереження балансу артиста
 *
 * Замінює дублюючі методи Artist::saveBalance() та Artist::getLog()
 * Забезпечує єдину логіку розрахунків з використанням QueryBuilder і DTO
 */
class ArtistBalanceService
{
    private ArtistBalanceQueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = new ArtistBalanceQueryBuilder();
    }

    /**
     * Розрахувати повний баланс артиста за період
     *
     * ВИПРАВЛЕНО: Тепер правильно розраховує для обох типів Artist та Partner
     */
    public function calculateBalance(
        int $artistId,
        int $quarter,
        int $year,
        int $currencyId,
        ?int $excludeInvoiceId = null
    ): ArtistBalanceDto {
        $balance = new ArtistBalanceDto($artistId, $currencyId, $quarter, $year);

        // Отримати назву валюти
        $currency = Currency::findOne(['currency_id' => $currencyId]);
        $balance->currencyName = $currency?->currency_name ?? '';

        // Отримати тип артиста
        $balance->artist = Artist::findOne($artistId);
        $isPartner = $balance->artist && $balance->artist->artist_type_id == 2;  // 2 = Partner

        // Розрахувати всі суми
        $balance->previousBalance = $this->queryBuilder::getPreviousBalance($artistId, $currencyId, $quarter, $year);
        $debitType = InvoiceType::DEBIT;
        $correctionType = InvoiceType::CORRECTION;

        if ($isPartner) {
            // Для Partner: окремо тип 1 (DEBIT) та тип 5 (CORRECTION)
            $partnerDebitIncome = $this->queryBuilder::getPartnerTotalIncome($artistId, $currencyId, $quarter, $year, $debitType);
            $partnerCorrectionIncome = $this->queryBuilder::getPartnerTotalIncome($artistId, $currencyId, $quarter, $year, $correctionType);
            $labelDebitIncome = $this->queryBuilder::getLabelIncomeFromPartner($artistId, $currencyId, $quarter, $year, $debitType);
            $labelCorrectionIncome = $this->queryBuilder::getLabelIncomeFromPartner($artistId, $currencyId, $quarter, $year, $correctionType);

            $balance->directIncome = $partnerDebitIncome;
            $balance->featureIncome = 0;  // Partner не має фітів
            $balance->labelIncome = $labelDebitIncome;
            $balance->correction = $partnerCorrectionIncome + $labelCorrectionIncome;
            $balance->totalIncome = $balance->directIncome + $balance->labelIncome;
        } else {
            // Для Artist: окремо рахуємо надходження з типів 1 і 5
            $directIncome = $this->queryBuilder::getArtistDirectIncome($artistId, $currencyId, $quarter, $year, $debitType);
            $totalIncome = $this->queryBuilder::getTotalIncomeForArtist($artistId, $currencyId, $quarter, $year, $debitType);
            $totalCorrection = $this->queryBuilder::getTotalIncomeForArtist($artistId, $currencyId, $quarter, $year, $correctionType);
            $labelDebitIncome = $this->queryBuilder::getLabelIncomeFromArtist($artistId, $currencyId, $quarter, $year, $debitType);
            $labelCorrectionIncome = $this->queryBuilder::getLabelIncomeFromArtist($artistId, $currencyId, $quarter, $year, $correctionType);

            $balance->directIncome = $directIncome;
            $balance->featureIncome = $totalIncome - $directIncome;  // Feature = Total - Direct
            $balance->labelIncome = $labelDebitIncome;
            $balance->correction = $totalCorrection + $labelCorrectionIncome;
            $balance->totalIncome = $totalIncome + $labelDebitIncome + $totalCorrection;
        }

        // Витрати
        $expenses = $this->queryBuilder::getExpensesByType($artistId, $currencyId, $quarter, $year);
        $balance->costs = $expenses[InvoiceType::COSTS]['amount'] ?? 0;
        $balance->advance = $expenses[InvoiceType::ADVANCE]['amount'] ?? 0;

        // Виплати та авансові
        $balance->paidAmount = $this->queryBuilder::getPayoutAmountForPeriod($artistId, $currencyId, $quarter, $year, $excludeInvoiceId);
        $balance->nextQuarterAdvance = $this->queryBuilder::getNextQuarterAdvance($artistId, $currencyId, $quarter, $year);

        // Виконати розрахунки
        $balance->calculate();

        return $balance;
    }

    /**
     * Збережити розрахований баланс в ArtistLog для історії
     */
    public function saveBalance(int $artistId, int $quarter, int $year, int $currencyId): void
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $balance = $this->calculateBalance($artistId, $quarter, $year, $currencyId);

            // Видалити старі записи за цей період
            ArtistLog::deleteAll([
                'artist_id' => $artistId,
                'currency_id' => $currencyId,
                'quarter' => $quarter,
                'year' => $year,
            ]);

            // Логування типів балансу відповідно до типів з БД
            $logTypes = [
                1 => $balance->previousBalance,   // Попередній баланс
                2 => $balance->costs,             // Витрати
                3 => $balance->nextQuarterAdvance, // Авансові
                4 => $balance->balanceBeforePayout, // Баланс (загальний)
                5 => $balance->totalIncome,       // Надходження типу DEBIT (1)
                6 => $balance->directIncome,      // Частка артиста
                7 => $balance->featureIncome,     // Частка артиста з фітів
                8 => $balance->labelIncome,       // Частка лейбла
                9 => 0,                           // Розрахунок для фіту (не використовується)
                10 => $balance->balanceBeforePayout, // Баланс на кінець періоду
                11 => $balance->amountToPay,      // Сума до виплати
                12 => $balance->correction,       // Надходження типу CORRECTION (5)
                13 => $balance->paidAmount,       // Сплачено за період
            ];

            foreach ($logTypes as $typeId => $amount) {
                $log = new ArtistLog();
                $log->artist_id = $artistId;
                $log->currency_id = $currencyId;
                $log->quarter = $quarter;
                $log->year = $year;
                $log->type_id = $typeId;
                $log->sum = round($amount, 2);

                if (!$log->save()) {
                    throw new \Exception('Не вдалося збережити ArtistLog: ' . implode(', ', $log->getFirstErrors()));
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Отримати баланс у форматі для звіту (сумісне з Artist::getLog)
     */
    public function getBalanceForReport(
        int $artistId,
        int $quarter,
        int $year,
        int $currencyId,
        string $currencyName,
        ?int $invoiceId = null
    ): array {
        $balance = $this->calculateBalance($artistId, $quarter, $year, $currencyId, $invoiceId);
        $balance->currencyName = $currencyName;

        // Заповнити значення
        $valuesByType = [
            1 => $balance->previousBalance,
            2 => $balance->costs, // Витрати за поточний період
            3 => $balance->nextQuarterAdvance, // Витрати за майбутній період
            4 => $balance->balanceBeforePayout,
            5 => $balance->totalIncome, // Надходження за типом DEBIT (1) артист + фіти + лейбл.
            6 => $balance->directIncome, // Частка артиста
            7 => $balance->featureIncome, // Частка артиста з фітів
            8 => $balance->labelIncome, // Частка лейбла
            10 => $balance->balanceBeforePayout, // Баланс на кінець періоду
            11 => $balance->amountToPay, // Сума до виплати
            12 => $balance->correction, // Надходження за типом CORRECTION (5)
            13 => $balance->paidAmount, // Сплачено за період
        ];

        echo $balance->totalExpenses;

        var_dump($valuesByType); // Debugging line to check the values by type

        $result = [];
        $balanceTypes = ArtistLogType::find()
            ->where(['active' => 1])
            ->orderBy('sort')
            ->all();

        // Ініціалізувати результат з типів балансу з БД
        foreach ($balanceTypes as $type) {
            if ($balance->artist->artist_type_id == 2 && $type->log_type_id == 7) {
                // Для Partner не показуємо Feature Income
                continue;
            }

            $result[$type->log_type_id] = [
                'name' => $balance->artist->translateDocument($type->name),
                'value' => $valuesByType[$type->log_type_id] ?? 0,
                'currency_name' => $currencyName,
            ];
        }

        return $result;
    }

    /**
     * Обновити депозити артиста у таблиці artist
     */
    public static function updateArtistDeposits(?int $artistId = null): array
    {
        $errors = [];

        if (null !== $artistId) {
            $artist = Artist::findOne($artistId);
            if (!$artist) {
                return $errors;
            }

            $deposits = self::getDepositsByArtist($artistId);

            foreach ($deposits as $deposit) {
                $currencyId = $deposit['currency_id'];
                $depositAmount = $deposit['deposit'];

                // Оновити депозити за валютами
                if ($currencyId == 1) { // EUR
                    if ($depositAmount != $artist->deposit_1) {
                        $errors[$artistId]['deposit_1'] = ['old' => $artist->deposit_1, 'new' => $depositAmount];
                        $artist->deposit_1 = $depositAmount;
                    }
                } elseif ($currencyId == 2) { // UAH
                    if ($depositAmount != $artist->deposit) {
                        $errors[$artistId]['deposit'] = ['old' => $artist->deposit, 'new' => $depositAmount];
                        $artist->deposit = $depositAmount;
                    }
                } elseif ($currencyId == 3) { // USD
                    if ($depositAmount != $artist->deposit_3) {
                        $errors[$artistId]['deposit_3'] = ['old' => $artist->deposit_3, 'new' => $depositAmount];
                        $artist->deposit_3 = $depositAmount;
                    }
                }
            }

            if ($errors) {
                $artist->save();
            }
        } else {
            // Обновити всіх художників
            self::updateAllArtistsDeposits();
        }

        return $errors;
    }

    /**
     * Отримати депозити артиста по валютах
     */
    private static function getDepositsByArtist(int $artistId): array
    {
        $builder = new ArtistBalanceQueryBuilder();
        return $builder::getDepositsByArtist($artistId);
    }

    /**
     * Обновити депозити для всіх художників
     */
    private static function updateAllArtistsDeposits(): void
    {
        $currencies = [
            1 => 'deposit_1',  // EUR
            2 => 'deposit',    // UAH
            3 => 'deposit_3',  // USD
        ];

        // Скинути всі депозити
        Yii::$app->db->createCommand("UPDATE `artist` SET `deposit`=0, `deposit_1`=0, `deposit_3`=0")->execute();

        // Пересчитати для кожної валюти
        foreach ($currencies as $currencyId => $fieldName) {
            Yii::$app->db->createCommand(
                "UPDATE `artist` a 
                INNER JOIN (
                    SELECT ii.artist_id, SUM(ii.amount) as deposit 
                        FROM invoice_items ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id 
                    WHERE i.invoice_status_id in (2, 4)
                        AND i.currency_id = :currency_id
                    GROUP BY ii.artist_id
                ) as b ON b.artist_id = a.id
                SET a.`{$fieldName}`= b.deposit"
            )->bindValue(':currency_id', $currencyId)->execute();
        }
    }
}

