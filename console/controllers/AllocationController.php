<?php

namespace console\controllers;


use common\models\t;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use backend\models\Invoice;
use backend\helpers\InvoiceAllocationService;
use yii\db\Exception;

class AllocationController extends Controller
{
    /**
     * Повністю перебудовує invoice_allocation по заданій валюті
     *
     * Використання:
     *   php yii allocation/rebuild 1
     *
     * @param int|null $currencyId
     * @param int|null $invoiceId
     * @return int
     * @throws Exception
     */
    public function actionRebuild(int $currencyId = null, int $invoiceId = null): int
    {
        $db = Yii::$app->db;
        $exitCode = ExitCode::OK;

        $lockName = 'allocation_rebuild_' . ($currencyId ?? 'all') . '_' . ($invoiceId ?? 'all');
        $lockAcquired = (int) $db->createCommand('SELECT GET_LOCK(:lock_name, 0)')
            ->bindValue(':lock_name', $lockName)
            ->queryScalar();

        if ($lockAcquired !== 1) {
            $this->stdout("⏭ Rebuild is already running for lock {$lockName}, skipping\n");
            return ExitCode::OK;
        }
        
        $this->stdout("🚀 Rebuilding invoice_allocation\n");
        $this->stdout("   Filters: currency_id=" . ($currencyId ?? 'all') . ", invoice_id=" . ($invoiceId ?? 'all') . "\n");

        // Перед перебудовою видаляємо биті allocation, які посилаються на видалені invoice_items.
        $deletedOrphaned = $db->createCommand("DELETE ia
            FROM invoice_allocation ia
            LEFT JOIN invoice_items ii_income ON ii_income.id = ia.income_item_id
            LEFT JOIN invoice_items ii_payout ON ii_payout.id = ia.payout_item_id
            WHERE ii_income.id IS NULL OR ii_payout.id IS NULL")->execute();
        $this->stdout("🧹 Deleted {$deletedOrphaned} orphaned allocation rows\n");

       $resetAllocatedSql = "UPDATE invoice inv
            SET inv.allocated = 0
            WHERE inv.allocated = 1
              AND EXISTS (
                    SELECT 1
                    FROM v_unallocated_payout_items v_u
                    WHERE v_u.invoice_id = inv.invoice_id
            )";

        $resetBinds = [];
        if ($currencyId) {
            $resetAllocatedSql .= " AND inv.currency_id = :currency_id";
            $resetBinds[':currency_id'] = $currencyId;
        }

        if ($invoiceId) {
            $resetAllocatedSql .= " AND inv.invoice_id = :invoice_id";
            $resetBinds[':invoice_id'] = $invoiceId;
        }

        $resetAllocated = $db->createCommand($resetAllocatedSql)
            ->bindValues($resetBinds)
            ->execute();
        $this->stdout("🔄 Reset {$resetAllocated} invoices from allocated=1 to allocated=0\n");

        // Після reset одразу позначаємо payout/cost/advance без залишку як allocated=1,
        // щоб не витрачати час на них в щоденному cron.
        $markedNoBalance = $this->markPayoutInvoicesWithoutBalanceAsAllocated($currencyId, $invoiceId);
        $this->stdout("✅ Marked {$markedNoBalance} payout invoices as allocated (no unallocated balance)\n");
        
        /* ------------------------------------------------------------------
         * 1️⃣ Видаляємо allocation для цієї валюти
         * ------------------------------------------------------------------ */
      /* $sql = "DELETE ia
                FROM invoice_allocation ia
                JOIN invoice_items ii_income
                  ON ii_income.id = ia.income_item_id
                JOIN invoice inv
                  ON inv.invoice_id = ii_income.invoice_id
                WHERE inv.currency_id = :currency_id";
        $binds = [':currency_id' => $currencyId];
        
        if ($invoiceId) {
            $sql .= " and inv.invoice_id = :invoice_id";
            $binds[':invoice_id'] = $invoiceId;
        }
        
        $deleted = $db->createCommand($sql)
            ->bindValues($binds)
            ->execute();
        
        $this->stdout("🧹 Deleted {$deleted} allocation rows\n");
        
       // exit();*/
        
        /* ------------------------------------------------------------------
         * 2️⃣ Отримуємо всі інвойси цієї валюти в хронології
         * ------------------------------------------------------------------ */
        $query = Invoice::find()
            ->andWhere(['in', 'invoice_type', [1, 2, 3, 4, 5]])
            ->andWhere(['in', 'invoice_status_id', [2, 4]]);

        if ($currencyId) {
            $query->andWhere(['currency_id' => $currencyId]);
        }

        if ($invoiceId) {
            // Примусовий запуск по конкретному інвойсу: дозволяємо ручний rerun.
            $query->andWhere(['invoice_id' => $invoiceId]);
        } else {
            // Для cron беремо лише інвойси, які ще не позначені як оброблені.
            $query->andWhere(['allocated' => 0]);

            // Для payout (2/3/4) запускаємо тільки якщо є реальний нерозподілений залишок.
            $query->andWhere("(
                invoice.invoice_type IN (1, 5)
                OR EXISTS (
                    SELECT 1
                    FROM v_unallocated_payout_items v_u
                    WHERE v_u.invoice_id = invoice.invoice_id
                      AND v_u.unallocated_amount > 0
                )
            )");
        }

        $invoices = $query
            ->orderBy(['invoice_id' => SORT_ASC])
            ->all();
        
        $this->stdout("📄 Found " . count($invoices) . " invoices\n");
        
            /* ------------------------------------------------------------------
             * 3️⃣ Послідовно проганяємо всі інвойси
             * ------------------------------------------------------------------ */
            foreach ($invoices as $invoice) {
               // t::log(sprintf("Start #%d", $invoice->invoice_id));
                $tx = $db->beginTransaction();
                try {
                $this->stdout(
                    sprintf(
                        "➡ Invoice #%d (type %d)\n",
                        $invoice->invoice_id,
                        $invoice->invoice_type
                    )
                );
                
                // Нарахування + Баланс
                if (in_array($invoice->invoice_type, [1, 5], true)) { // Нарахування + Баланс
                    $sum = InvoiceAllocationService::allocateIncomeItemsToAdvanceItems(
                        $invoice->invoice_id
                    );

                    // Щоб не проганяти ті ж самі income інвойси щодня,
                    // фіксуємо їх як оброблені.
                    if ($invoice->allocated != 1) {
                        $invoice->allocated = 1;
                        $invoice->save(false, ['allocated']);
                    }
                } else { // Виплата + Витрати + Аванс
                    $sum = InvoiceAllocationService::allocateUnallocatedPayoutItems($invoice);

                    // ✅ ПІСЛЯ allocation — перевіряємо статус
                    $this->updatePayoutInvoiceStatusIfCalculated($invoice);
                }
                    $tx->commit();
                    $this->stdout(
                        sprintf(
                            "✅ Invoice #%d completed\n",
                            $invoice->invoice_id
                        )
                    );

                  // if($sum > 0) {
                  //     t::log(sprintf("Completed #%d , sum: %s", $invoice->invoice_id, $sum));
                 //  }
                } catch (Throwable $e) {
                    $tx->rollBack();
                    $this->stderr("❌ ERROR: {$e->getMessage()}\n");
                    //t::log(sprintf("Error #%d : " . $e->getMessage(), $invoice->invoice_id));
                    $exitCode = ExitCode::UNSPECIFIED_ERROR;
                    break;
                }
            }
        
        $this->stdout("✅ Rebuild completed successfully\n");
        //t::log("Rebuild completed successfully");
        
        $db->createCommand('SELECT RELEASE_LOCK(:lock_name)')
            ->bindValue(':lock_name', $lockName)
            ->queryScalar();

        return $exitCode;
    }
    
    
    private function updatePayoutInvoiceStatusIfCalculated(Invoice $invoice): void
    {
        if (!in_array($invoice->invoice_type, [2, 3, 4], true)) {
            return;
        }
        
        if ($invoice->allocated == 1) {
            return; // ✅ вже розрахований
        }
        
        $hasUnallocated = (bool) Yii::$app->db->createCommand("
        SELECT 1
        FROM v_payout_balance
        WHERE invoice_id = :invoice_id
          AND unallocated_amount > 0
        LIMIT 1
    ")->bindValue(':invoice_id', $invoice->invoice_id)->queryScalar();
        
        if (!$hasUnallocated) {
            $invoice->allocated = 1;
            $invoice->save(false);
            
            $this->stdout(
                "✅ Invoice #{$invoice->invoice_id} marked as allocated\n"
            );
        }
    }

    private function markPayoutInvoicesWithoutBalanceAsAllocated(?int $currencyId = null, ?int $invoiceId = null): int
    {
        $sql = "UPDATE invoice inv
            SET inv.allocated = 1
            WHERE inv.allocated = 0
              AND inv.invoice_type IN (2, 3, 4)
              AND inv.invoice_status_id IN (2, 4)
              AND NOT EXISTS (
                    SELECT 1
                    FROM v_unallocated_payout_items v_u
                    WHERE v_u.invoice_id = inv.invoice_id
                      AND v_u.unallocated_amount > 0
            )";

        $binds = [];
        if ($currencyId) {
            $sql .= " AND inv.currency_id = :currency_id";
            $binds[':currency_id'] = $currencyId;
        }

        if ($invoiceId) {
            $sql .= " AND inv.invoice_id = :invoice_id";
            $binds[':invoice_id'] = $invoiceId;
        }

        return Yii::$app->db->createCommand($sql)
            ->bindValues($binds)
            ->execute();
    }
    
}