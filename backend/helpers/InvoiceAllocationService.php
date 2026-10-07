<?php

namespace backend\helpers;


use backend\models\Invoice;
use backend\models\InvoiceItems;
use Yii;
use yii\db\Exception;

class InvoiceAllocationService
{
    /**
     * Розподіляє суму доходу по авансах артиста, починаючи з найстарішого.
     *
     * @param int $incomeInvoiceId ID рахунку доходу, який потрібно розподілити.
     * @throws Exception
     *
     * Покриває доходами (type 1/5) раніше створені аванси (type 3)
     */
    public static function allocateIncomeItemsToAdvanceItems(int $incomeInvoiceId): float
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $sum = 0;
            // 1️⃣ Беремо сам інвойс доходу
            $incomeInvoice = (new \yii\db\Query())
                ->from('invoice')
                ->where(['invoice_id' => $incomeInvoiceId])
                ->one();

            if (!$incomeInvoice) {
                $transaction->commit();
                return $sum;
            }

            $currencyId = (int)$incomeInvoice['currency_id'];
            $incomeYear = (int)$incomeInvoice['year'];
            $incomeQ    = (int)$incomeInvoice['quarter'];

            // 2️⃣ Отримати ВСІ income-items з їх непокритими сумами за один запит
            $incomeItems = Yii::$app->db->createCommand("
                SELECT 
                    ii.id,
                    ii.artist_id,
                    ii.amount - IFNULL(SUM(ia.amount), 0) as unpaid_amount
                FROM invoice_items ii
                LEFT JOIN invoice_allocation ia ON ia.income_item_id = ii.id
                WHERE ii.invoice_id = :invoice_id
                  AND ii.amount > 0
                GROUP BY ii.id
                HAVING unpaid_amount > 0
                ORDER BY ii.id ASC
            ")->bindValue(':invoice_id', $incomeInvoiceId)->queryAll();


            foreach ($incomeItems as $income) {
                $incomeItemId = (int)$income['id'];
                $artistId     = (int)$income['artist_id'];
                $remainingIncome = (float)$income['unpaid_amount'];

                if ($remainingIncome <= 0) {
                    continue;
                }

                // 3️⃣ Шукаємо АВАНСИ ≤ поточному кварталу доходу
                $advanceItems = Yii::$app->db->createCommand("
                SELECT
                    pb.payout_item_id,
                    pb.unallocated_amount
                FROM v_payout_balance pb
                JOIN invoice i ON i.invoice_id = pb.invoice_id
                WHERE pb.invoice_type in (3, 4) -- аванси і витрати
                  AND pb.artist_id = :artist_id
                  AND pb.currency_id = :currency_id
                  AND pb.unallocated_amount > 0
                  AND (
                        pb.year < :income_year
                     OR (pb.year = :income_year AND pb.quarter <= :income_quarter)
                  )
                AND i.invoice_status_id in (2, 4)
                ORDER BY pb.year ASC, pb.quarter ASC, i.invoice_id ASC, pb.payout_item_id ASC
            ")->bindValues([
                    ':artist_id'      => $artistId,
                    ':currency_id'    => $currencyId,
                    ':income_year'    => $incomeYear,
                    ':income_quarter' => $incomeQ,
                ])->queryAll();

                foreach ($advanceItems as $adv) {
                    if ($remainingIncome <= 0) {
                        break;
                    }

                    $advanceItemId = (int)$adv['payout_item_id'];
                    $unallocated   = (double)$adv['unallocated_amount'];

                    if ($unallocated <= 0) {
                        continue;
                    }

                    $allocAmount = min($remainingIncome, $unallocated);

                    $sum += $allocAmount;

                    Yii::$app->db->createCommand()
                        ->insert('invoice_allocation', [
                        'income_item_id' => $incomeItemId,
                        'payout_item_id' => $advanceItemId,
                        'amount'         => $allocAmount,
                    ])->execute();

                    $remainingIncome -= $allocAmount;
                }
            }

            $transaction->commit();
            return $sum;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
    
    /**
     * Розподіляє суму виплати по доходах артиста, починаючи з найстарішого.
     *
     * @param Invoice $payoutInvoice
     * @throws Exception Покриває авансами (type 3) раніше створені доходи (type 1/5)
     */
    public static function allocatePayoutItemsToIncome(Invoice $payoutInvoice): void
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $currencyId = $payoutInvoice->currency_id;
            // payout items (відʼємні суми)
            $payoutItems = $payoutInvoice->getInvoiceItems()->all();

            /* @var InvoiceItems[] $payoutItems */
            foreach ($payoutItems as $payoutItem) {
                $remainingPayout = self::getUnallocatedPayoutAmount($payoutItem->id);
                if ($remainingPayout <= 0) {
                    continue;
                }

                // income items цього артиста FIFO по кварталах
                $incomeItems = InvoiceItems::find()
                    ->joinWith('invoice')
                    ->where([
                        'invoice.invoice_type' => [1, 5],
                        'invoice.currency_id' => $currencyId,
                        'invoice_items.artist_id' => $payoutItem->artist_id
                    ])
                    ->andWhere(['>', 'invoice_items.amount', 0])
                    ->orderBy([
                        'invoice.year' => SORT_ASC,
                        'invoice.quarter' => SORT_ASC,
                        'invoice_items.id' => SORT_ASC
                    ])
                    ->all();

                foreach ($incomeItems as $incomeItem) {
                    $unpaidIncome = self::getUnpaidIncomeAmount($incomeItem->id);
                    if ($unpaidIncome <= 0) {
                        continue;
                    }

                    $allocAmount = min($remainingPayout, $unpaidIncome);

                    Yii::$app->db->createCommand()
                        ->insert('invoice_allocation', [
                        'income_item_id' => $incomeItem->id,
                        'payout_item_id' => $payoutItem->id,
                        'amount' => $allocAmount,
                    ])->execute();

                    $remainingPayout -= $allocAmount;
                    if ($remainingPayout <= 0) {
                        break;
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
    
    /**
     * Розподіляє всі непокриті виплати по непокритих доходах артиста, починаючи з найстарішого.
     *
     * Використовується для масового розподілення, наприклад після імпорту даних або зміни логіки розподілення.
     *
     * Логіка:
     * 1️⃣ Беремо всі payout / advance з непокритим залишком
     * 2️⃣ Для кожного payout FIFO покриваємо income ТІЛЬКИ ДО ЦЬОГО КВАРТАЛУ (або раніше)
     */
    public static function allocateUnallocatedPayoutItems(Invoice $invoice): float
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $sum = 0;
            $db = Yii::$app->db;

            // 1️⃣ Всі payout / advance з непокритим залишком
            $payoutItems = $db->createCommand("
                SELECT
                    pb.payout_item_id,
                    pb.artist_id,
                    pb.currency_id,
                    pb.unallocated_amount,
                    pb.year AS payout_year,
                    pb.quarter AS payout_quarter
                FROM v_payout_balance pb
                WHERE pb.unallocated_amount > 0
                and pb.invoice_id = :invoice_id
                ORDER BY pb.artist_id ASC, pb.payout_item_id ASC
            ")->bindValue(':invoice_id', $invoice->invoice_id)
                ->queryAll();

            foreach ($payoutItems as $payout) {
                $payoutItemId = (int) $payout['payout_item_id'];
                $artistId     = (int) $payout['artist_id'];
                $currencyId   = (int) $payout['currency_id'];
                $remaining    = (double) $payout['unallocated_amount'];
                $pYear        = (int) $payout['payout_year'];
                $pQuarter     = (int) $payout['payout_quarter'];

                if ($remaining <= 0) {
                    continue;
                }

                // 2️⃣ FIFO income ТІЛЬКИ ДО ЦЬОГО КВАРТАЛУ (або раніше)
                $incomeItems = $db->createCommand("
                SELECT
                    ib.income_item_id,
                    ib.unpaid_amount,
                    ib.year,
                    ib.quarter
                FROM v_income_balance ib
                JOIN invoice i ON i.invoice_id = ib.invoice_id
                WHERE ib.artist_id = :artist_id
                  AND ib.currency_id = :currency_id
                  AND ib.unpaid_amount > 0
                  AND (
                        ib.year < :payout_year
                     OR (ib.year = :payout_year AND ib.quarter <= :payout_quarter)
                  )
                AND i.invoice_status_id in (2, 4)
                ORDER BY i.invoice_id ASC, ib.income_item_id ASC
            ")->bindValues([
                    ':artist_id'     => $artistId,
                    ':currency_id'   => $currencyId,
                    ':payout_year'   => $pYear,
                    ':payout_quarter'=> $pQuarter,
                ])->queryAll();

                foreach ($incomeItems as $income) {

                    if ($remaining <= 0) {
                        break;
                    }

                    $incomeItemId = (int) $income['income_item_id'];
                    $unpaid       = (double) $income['unpaid_amount'];

                    if ($unpaid <= 0) {
                        continue;
                    }

                    $allocAmount = min($remaining, $unpaid);
                    $sum += $allocAmount;

                    $db->createCommand()
                        ->insert('invoice_allocation', [
                        'income_item_id' => $incomeItemId,
                        'payout_item_id' => $payoutItemId,
                        'amount'         => $allocAmount,
                    ])->execute();

                    $remaining -= $allocAmount;
                }
            }

            $transaction->commit();
            return $sum;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
    
    /**
     * Видаляє всі розподілення для заданого пункту доходу (income item).
     *
     * @param int|array $ids
     * @throws Exception
     */
    public static function deleteAllocation(int|array $ids): void
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        Yii::$app->db->createCommand()
            ->delete('invoice_allocation', [
                'or',
                ['in', 'payout_item_id', $ids],
                ['in', 'income_item_id', $ids],
            ])
            ->execute();
    }
    
    /**
     * Видаляє всі розподілення для заданого пункту виплати (payout item).
     *
     * @param int $incomeItemId
     * @return float
     * @throws Exception
     */
    private static function getUnpaidIncomeAmount(int $incomeItemId): float
    {
        return (float) Yii::$app->db->createCommand("
            SELECT ii.amount - IFNULL(SUM(ia.amount), 0)
            FROM invoice_items ii
            LEFT JOIN invoice_allocation ia ON ia.income_item_id = ii.id
            WHERE ii.id = :id
            GROUP BY ii.amount
            ")->bindValue(':id', $incomeItemId)
            ->queryScalar();
    }
    
    private static function getUnallocatedPayoutAmount(int $payoutItemId): float
    {
        return abs((float) Yii::$app->db->createCommand("
            SELECT ABS(ii.amount) - IFNULL(SUM(ia.amount), 0)
            FROM invoice_items ii
            LEFT JOIN invoice_allocation ia ON ia.payout_item_id = ii.id
            WHERE ii.id = :id
            GROUP BY ii.amount
            ")->bindValue(':id', $payoutItemId)
                ->queryScalar());
    }
}
