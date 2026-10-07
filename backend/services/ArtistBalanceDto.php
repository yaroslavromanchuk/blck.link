<?php

namespace backend\services;

/**
 * DTO для передачі розраховуваних даних балансу артиста
 * 
 * Забезпечує типізацію даних та полегшує роботу з розрахунками
 */
class ArtistBalanceDto
{
    // Базові суми
    public float $previousBalance = 0.0;      // Баланс до цього періоду
    public float $directIncome = 0.0;         // Прямий дохід артиста
    public float $featureIncome = 0.0;        // Дохід з фітів
    public float $labelIncome = 0.0;          // Дохід лейбла від артиста
    public float $totalIncome = 0.0;          // Загальний дохід
    
    // Витрати
    public float $costs = 0.0;                // Витрати (комісії)
    public float $advance = 0.0;              // Авансові платежі
    public float $totalExpenses = 0.0;        // Загальні витрати
    
    // Розраховані суми
    public float $balanceBeforePayout = 0.0;  // Баланс перед виплатою (попередня сума + дохід - витрати)
    public float $nextQuarterAdvance = 0.0;   // Аванс на наступний квартал
    public float $paidAmount = 0.0;           // Вже виплачено за період
    public float $amountToPay = 0.0;          // Сума до виплати
    
    // Метаінформація
    public int $artistId;
    public int $currencyId;
    public int $quarter;
    public int $year;
    public string $currencyName = '';

    public function __construct(int $artistId, int $currencyId, int $quarter, int $year)
    {
        $this->artistId = $artistId;
        $this->currencyId = $currencyId;
        $this->quarter = $quarter;
        $this->year = $year;
    }

    /**
     * Розрахувати загальні витрати
     */
    public function calculateTotalExpenses(): void
    {
        $this->totalExpenses = $this->costs + $this->advance;
    }

    /**
     * Розрахувати баланс перед виплатою
     */
    public function calculateBalanceBeforePayout(): void
    {
        $this->balanceBeforePayout = $this->previousBalance 
            + $this->directIncome 
            + $this->featureIncome 
            - $this->totalExpenses;
    }

    /**
     * Розрахувати суму до виплати
     */
    public function calculateAmountToPay(): void
    {
        $this->amountToPay = $this->balanceBeforePayout - $this->paidAmount;
        if ($this->amountToPay < 0) {
            $this->amountToPay = 0;
        }
    }

    /**
     * Виконати всі розрахунки
     */
    public function calculate(): void
    {
        $this->calculateTotalExpenses();
        $this->calculateBalanceBeforePayout();
        $this->calculateAmountToPay();
    }

    /**
     * Повернути дані як масив для логування
     */
    public function toArray(): array
    {
        return [
            'artist_id' => $this->artistId,
            'currency_id' => $this->currencyId,
            'currency_name' => $this->currencyName,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'previous_balance' => $this->previousBalance,
            'direct_income' => $this->directIncome,
            'feature_income' => $this->featureIncome,
            'label_income' => $this->labelIncome,
            'total_income' => $this->totalIncome,
            'costs' => $this->costs,
            'advance' => $this->advance,
            'total_expenses' => $this->totalExpenses,
            'balance_before_payout' => $this->balanceBeforePayout,
            'next_quarter_advance' => $this->nextQuarterAdvance,
            'paid_amount' => $this->paidAmount,
            'amount_to_pay' => $this->amountToPay,
        ];
    }

    /**
     * Отримати форматовані дані для звіту
     */
    public function toReport(): array
    {
        $this->calculate();
        
        return [
            '1_previous_balance' => [
                'name' => 'Попередній баланс',
                'value' => $this->previousBalance,
                'currency' => $this->currencyName,
            ],
            '2_direct_income' => [
                'name' => 'Прямий дохід',
                'value' => $this->directIncome,
                'currency' => $this->currencyName,
            ],
            '3_feature_income' => [
                'name' => 'Дохід з фітів',
                'value' => $this->featureIncome,
                'currency' => $this->currencyName,
            ],
            '4_total_income' => [
                'name' => 'Загальний дохід',
                'value' => $this->totalIncome,
                'currency' => $this->currencyName,
            ],
            '5_costs' => [
                'name' => 'Витрати',
                'value' => $this->costs,
                'currency' => $this->currencyName,
            ],
            '6_advance' => [
                'name' => 'Авансові платежі',
                'value' => $this->advance,
                'currency' => $this->currencyName,
            ],
            '7_balance_before_payout' => [
                'name' => 'Баланс перед виплатою',
                'value' => $this->balanceBeforePayout,
                'currency' => $this->currencyName,
            ],
            '8_next_quarter_advance' => [
                'name' => 'Аванс на наступний квартал',
                'value' => $this->nextQuarterAdvance,
                'currency' => $this->currencyName,
            ],
            '9_paid_amount' => [
                'name' => 'Вже виплачено',
                'value' => $this->paidAmount,
                'currency' => $this->currencyName,
            ],
            '10_amount_to_pay' => [
                'name' => 'Сума до виплати',
                'value' => $this->amountToPay,
                'currency' => $this->currencyName,
            ],
        ];
    }
}

