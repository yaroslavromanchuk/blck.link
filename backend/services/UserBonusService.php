<?php

namespace backend\services;

use backend\models\UserBalance;
use backend\models\UserBonus;
use Yii;

/**
 * Сервіс для оптимізації розрахунку бонусів користувачів
 *
 * Централізує логіку для:
 * - Отримання бонусів за лейблами
 * - Отримання бонусів за артистами
 * - Отримання бонусів за треками
 * - Кешування результатів для зниження кількості запитів
 */
class UserBonusService
{
    private array $labelBonusCache = [];
    private array $artistBonusCache = [];
    private array $trackBonusCache = [];

    /**
     * Отримати усіх користувачів з бонусами для лейбла
     * Результати кешуються
     */
    public function getUserToLabel(int $labelId): array
    {
        if (!isset($this->labelBonusCache[$labelId])) {
            $this->labelBonusCache[$labelId] = UserBonus::find()
                ->where(['label_id' => $labelId])
                ->all();
        }
        return $this->labelBonusCache[$labelId];
    }

    /**
     * Отримати усіх користувачів з бонусами для артиста
     * Результати кешуються
     */
    public function getUserToArtist(int $artistId): array
    {
        if (!isset($this->artistBonusCache[$artistId])) {
            $this->artistBonusCache[$artistId] = UserBonus::find()
                ->where(['artist_id' => $artistId])
                ->all();
        }
        return $this->artistBonusCache[$artistId];
    }

    /**
     * Отримати усіх користувачів з бонусами для треку
     * Результати кешуються
     */
    public function getUserToTrack(int $trackId): array
    {
        if (!isset($this->trackBonusCache[$trackId])) {
            $this->trackBonusCache[$trackId] = UserBonus::find()
                ->where(['track_id' => $trackId])
                ->all();
        }
        return $this->trackBonusCache[$trackId];
    }

    /**
     * Очистити кеш (використовується після зміни даних)
     */
    public function clearCache(): void
    {
        $this->labelBonusCache = [];
        $this->artistBonusCache = [];
        $this->trackBonusCache = [];
    }

    /**
     * Отримати всі бонуси для конкретної сутності
     */
    public static function getBonusesByEntity(string $entityType, int $entityId): array
    {
        return UserBonus::find()
            ->where(["{$entityType}_id" => $entityId])
            ->all();
    }

    /**
     * Перевірити чи вже розраховані бонуси для користувача
     */
    public static function isBalanceAlreadyCalculated(int $invoiceId, int $userId, int $currencyId, ?int $labelId = null, ?int $artistId = null): bool
    {
        $query = UserBalance::find()
            ->where([
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'currency_id' => $currencyId,
            ]);

        if ($labelId !== null) {
            $query->andWhere(['label_id' => $labelId]);
        } elseif ($artistId !== null) {
            $query->andWhere(['artist_id' => $artistId]);
        }

        return $query->exists();
    }

    /**
     * Додати баланс користувача з перевіркою дублікатів
     */
    public static function addBalanceIfNotExists(array $data): bool
    {
        // Перевірити чи уже існує
        if (self::isBalanceAlreadyCalculated(
            $data['invoice_id'],
            $data['user_id'],
            $data['currency_id'],
            $data['label_id'] ?? null,
            $data['artist_id'] ?? null
        )) {
            return true; // Уже існує
        }

        return UserBalance::add($data);
    }

    /**
     * Масово видалити бонуси для інвойсу (для пересчету)
     */
    public static function deleteBalancesForInvoice(int $invoiceId): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()
                ->delete('user_balance', ['invoice_id' => $invoiceId])
                ->execute();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Отримати всі бонуси користувача за період
     */
    public static function getUserBalancesForPeriod(
        int $userId,
        int $quarter,
        int $year,
        int $currencyId
    ): array {
        return Yii::$app->db->createCommand("
            SELECT 
                ub.*,
                i.quarter,
                i.year,
                a.name as artist_name,
                t.name as track_name,
                sl.name as label_name
            FROM user_balance ub
            INNER JOIN invoice i ON i.invoice_id = ub.invoice_id
            LEFT JOIN artist a ON a.id = ub.artist_id
            LEFT JOIN track t ON t.id = ub.track_id
            LEFT JOIN sub_label sl ON sl.id = ub.label_id
            WHERE ub.user_id = :user_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ub.currency_id = :currency_id
            ORDER BY ub.date_added DESC
        ")
            ->bindValues([
                ':user_id' => $userId,
                ':quarter' => $quarter,
                ':year' => $year,
                ':currency_id' => $currencyId,
            ])
            ->queryAll();
    }

    /**
     * Отримати загальну суму бонусів користувача за період
     */
    public static function getUserTotalBonusForPeriod(
        int $userId,
        int $quarter,
        int $year,
        int $currencyId
    ): float {
        return (float) Yii::$app->db->createCommand("
            SELECT SUM(ub.amount) as total
            FROM user_balance ub
            INNER JOIN invoice i ON i.invoice_id = ub.invoice_id
            WHERE ub.user_id = :user_id
              AND i.quarter = :quarter
              AND i.year = :year
              AND ub.currency_id = :currency_id
        ")
            ->bindValues([
                ':user_id' => $userId,
                ':quarter' => $quarter,
                ':year' => $year,
                ':currency_id' => $currencyId,
            ])
            ->queryScalar() ?? 0;
    }
}

