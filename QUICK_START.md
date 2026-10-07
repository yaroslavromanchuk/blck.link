# 🚀 QUICK START: Оптимізована система роялті

## Де знайти всі нові файли?

```
backend/
├── services/                          ← 🆕 НОВІ СЕРВІСИ
│   ├── ArtistBalanceService.php      # Розрахунок балансу артиста
│   ├── ArtistBalanceQueryBuilder.php # SQL запити для балансу
│   ├── ArtistBalanceDto.php          # Data Transfer Object
│   └── UserBonusService.php          # Управління бонусами
│
├── models/
│   ├── Artist.php                     # ✏️ ОНОВЛЕНО (методи перенесені)
│   ├── Invoice.php                    # ✏️ ОПТИМІЗОВАНО (запити)
│   └── InvoiceType.php                # ✏️ ОНОВЛЕНО (константи)
│
└── helpers/
    ├── InvoiceAllocationService.php   # ✏️ ОПТИМІЗОВАНО (батче-запити)
    └── InvoiceService.php             # ✏️ ОНОВЛЕНО (транзакції)
```

---

## 📖 Як розпочати користування?

### 1. Розрахувати баланс артиста

```php
<?php
use backend\services\ArtistBalanceService;

$service = new ArtistBalanceService();

// Розрахувати баланс
$balance = $service->calculateBalance(
    artistId: 123,           // ID артиста
    quarter: 1,              // 1-4 квартал
    year: 2024,              // Рік
    currencyId: 2            // 1=EUR, 2=UAH, 3=USD
);

// Отримати результати
echo "Прямий дохід: " . $balance->directIncome;
echo "Дохід з фітів: " . $balance->featureIncome;
echo "До виплати: " . $balance->amountToPay;

// Збережти у БД
$service->saveBalance(123, 1, 2024, 2);
```

### 2. Отримати дані для звіту

```php
<?php
use backend\services\ArtistBalanceService;

$service = new ArtistBalanceService();

// Отримати для звіту (сумісно зі старим методом)
$reportData = $service->getBalanceForReport(
    artistId: 123,
    quarter: 1,
    year: 2024,
    currencyId: 2,
    currencyName: 'UAH',
    invoiceId: null  // опціонально
);

// Результат - масив типів балансу
foreach ($reportData as $typeId => $data) {
    echo $data['name'] . ": " . $data['value'] . " " . $data['currency_name'];
}
```

### 3. Управління бонусами користувачів

```php
<?php
use backend\services\UserBonusService;

$service = new UserBonusService();

// Отримати бонуси за лейблом (з кешуванням)
$bonuses = $service->getUserToLabel(5);

// Отримати бонуси за артистом (з кешуванням)
$bonuses = $service->getUserToArtist(10);

// Додати баланс з перевіркою дублікатів
UserBonusService::addBalanceIfNotExists([
    'invoice_id' => 100,
    'user_id' => 5,
    'currency_id' => 2,
    'label_id' => 3,
    'all_sum' => 10000,
    'percentage' => 10,
    'amount' => 1000,
]);

// Видалити всі бонуси для переліку (для пересчету)
UserBonusService::deleteBalancesForInvoice(100);

// Отримати всі бонуси користувача за період
$balances = UserBonusService::getUserBalancesForPeriod(
    userId: 5,
    quarter: 1,
    year: 2024,
    currencyId: 2
);
```

### 4. Розподіл доходів по авансам

```php
<?php
use backend\helpers\InvoiceAllocationService;

// Розподілити дохід по авансам (автоматично з батче-запитами)
$allocatedAmount = InvoiceAllocationService::allocateIncomeItemsToAdvanceItems(100);
echo "Розподілено: " . $allocatedAmount;

// Розподілити виплату по доходам
$invoice = Invoice::findOne(101);
InvoiceAllocationService::allocatePayoutItemsToIncome($invoice);

// Масово розподілити (для пересчету)
$invoice = Invoice::findOne(102);
$totalAllocated = InvoiceAllocationService::allocateUnallocatedPayoutItems($invoice);
```

### 5. Використання константи типів інвойсів

```php
<?php
use backend\models\InvoiceType;

// Замість магічних чисел
if ($invoice->invoice_type == InvoiceType::DEBIT) {  // замість == 1
    // це дохід
}

// Використовувати масиви типів для запитів
$incomes = InvoiceItems::find()
    ->joinWith('invoice')
    ->where(['invoice.invoice_type' => InvoiceType::INCOME_TYPES])  // [1, 5]
    ->all();
```

---

## 📊 Порівняння: Старий VS Новий код

### Розрахунок балансу

**❌ СТАРЕ (дублювання):**
```php
// Artist.php - два окремих методи з однаковою логікою
public function saveBalance() { ... 200 рядків коду ... }
public static function getLog() { ... 200 рядків коду ... }
```

**✅ НОВЕ (централізовано):**
```php
// Artist.php - простий делегат
public function saveBalance() {
    $service = new ArtistBalanceService();
    $service->saveBalance(...);
}

// ArtistBalanceService.php - єдина реалізація
public function saveBalance() { ... }
```

### N+1 запити

**❌ СТАРЕ:**
```php
foreach ($incomeItems as $income) {
    $remaining = self::getUnpaidIncomeAmount($income['id']); // запит у циклі!
    // 100 income items = 100 запитів
}
```

**✅ НОВЕ:**
```php
// Один батче-запит
$incomeItems = Yii::$app->db->createCommand("
    SELECT ii.id,
           ii.amount - IFNULL(SUM(ia.amount), 0) as unpaid
    FROM invoice_items ii
    LEFT JOIN invoice_allocation ia ON ia.income_item_id = ii.id
    GROUP BY ii.id
")->queryAll(); // 1 запит для 100 items
```

### Безпека операцій

**❌ СТАРЕ:**
```php
// Якщо помилка в середині циклу - дані в неконсистентного стану
foreach ($artists as $artist) {
    $row->save(); // може не збіглася!
}
```

**✅ НОВЕ:**
```php
$transaction = Yii::$app->db->beginTransaction();
try {
    foreach ($artists as $artist) {
        $row->save();
    }
    $transaction->commit(); // або rollBack()
} catch (\Exception $e) {
    $transaction->rollBack(); // все повертається назад
}
```

---

## 🎯 Найчастіше питання

### Q: Які функції замінилися?

| Старе | Нове |
|-------|------|
| `Artist::saveBalance()` | `ArtistBalanceService::saveBalance()` |
| `Artist::getLog()` | `ArtistBalanceService::getBalanceForReport()` |
| `Artist::calculationDeposit()` | `ArtistBalanceService::updateArtistDeposits()` |

### Q: Чи потрібно оновлювати вже написаний код?

**Ні!** Всі старі методи ще працюють. Вони тепер просто вільні на нові сервіси.

```php
// Це все ще працює (не потребує змін):
$artist = Artist::findOne(123);
$artist->saveBalance(1, 2, 2024);

// Але краще використовувати напряму:
$service = new ArtistBalanceService();
$service->saveBalance(123, 1, 2024, 2);
```

### Q: Як отримати всі дані у старому форматі?

```php
// Використовувати getBalanceForReport() - повертає той же формат
$service = new ArtistBalanceService();
$data = $service->getBalanceForReport(123, 1, 2024, 2, 'UAH');

// Або через Artist (стара API)
$data = Artist::getLog(123, 1, 2024, 2, 'UAH');
```

### Q: Де знайти SQL запити для оптимізації?

У `ArtistBalanceQueryBuilder::class` - всі запити централізовані тут.

---

## 🔍 Отримання помощи

### Документація:
- `OPTIMIZATION_DOCUMENTATION.md` - детальна документація
- `CHANGELOG.md` - список всіх змін
- Docblocks у кожному методі

### Синтаксична перевірка:
```bash
php -l backend/services/ArtistBalanceService.php
php -l backend/services/ArtistBalanceQueryBuilder.php
php -l backend/services/ArtistBalanceDto.php
php -l backend/services/UserBonusService.php
```

### Тестування:
```bash
# Розрахувати баланс одного артиста
php yii artist/calculate --artist-id=123 --quarter=1 --year=2024
```

---

## ✅ Контрольний список

Перед початком користування:

- [ ] Прочитав цей файл
- [ ] Розумію різницю між старим і новим кодом
- [ ] Знаю де знайти нові файли
- [ ] Можу використовувати нові сервіси
- [ ] Знаю як отримати допомогу

---

**Готово! 🎉 Тепер можеш розпочати користування оптимізованою системою роялті.**

Будь які питання - див. `OPTIMIZATION_DOCUMENTATION.md`

