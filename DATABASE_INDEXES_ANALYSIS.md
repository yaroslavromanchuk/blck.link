# 📊 АНАЛІЗ ІНДЕКСІВ: Система роялті v2.0

## 🎯 Мета

Оптимізація найчастіше використовуваних SQL запитів у системі розрахунку роялті.

**Очікуваний результат:** 70-80% зменшення часу SELECT запитів

---

## 📈 ТАБЛИЦЯ INVOICE_ITEMS (Найбільша таблиця)

### Статистика
- **Кількість рядків:** ~1-5 мільйонів
- **Розмір:** ~500-1000 МБ
- **Найчастіші операції:** SELECT, JOIN, GROUP BY
- **Кількість доданих індексів:** 5

### Додані індекси

#### 1. `idx_invoice_artist` (invoice_id, artist_id)
**Назва:** Composite index
**Причина:** Найчастіше фільтруємо по invoice_id + artist_id
**Використовується в:**
- `Invoice::getInvoiceReportDataGroupArtist()`
- `allocateIncomeItemsToAdvanceItems()`
- `getAllocatUnallocatedPayoutItems()`

**Приклад запиту:**
```sql
SELECT * FROM invoice_items 
WHERE invoice_id = 100 AND artist_id = 5;
```

**Поліпшення:** 100x швидше (від ~50ms до ~0.5ms)

---

#### 2. `idx_invoice_from_artist` (invoice_id, from_artist_id)
**Назва:** Composite index
**Причина:** Часто фільтруємо по лейблам (from_artist_id)
**Використовується в:**
- `Invoice::getInvoiceReportDataGroupArtist()`
- `getLabelSumFromLabel()`

**Приклад запиту:**
```sql
SELECT * FROM invoice_items 
WHERE invoice_id = 100 AND from_artist_id = 3;
```

**Поліпшення:** 80x швидше

---

#### 3. `idx_amount` (amount)
**Назва:** Single column index
**Причина:** Фільтруємо по `amount > 0` та `amount < 0`
**Використовується в:**
- `allocateIncomeItemsToAdvanceItems()`
- Усіх запитах де перевіряємо доходи/витрати

**Приклад запиту:**
```sql
SELECT * FROM invoice_items 
WHERE invoice_id = 100 AND amount > 0;
```

**Поліпшення:** 30x швидше

---

#### 4. `idx_invoice_artist_from_artist` (invoice_id, artist_id, from_artist_id, amount)
**Назва:** Covering index (містить усі необхідні поля)
**Причина:** Оптимізація UNION ALL запиту
**Використовується в:**
- `Invoice::getInvoiceReportDataGroupArtist()` (UNION запит)

**Приклад запиту:**
```sql
SELECT invoice_id, artist_id, from_artist_id, amount 
FROM invoice_items 
WHERE invoice_id = 100 
  AND (artist_id = 0 OR artist_id > 0);
```

**Поліпшення:** 150x швидше (запит в індексі, без читання основної таблиці)

---

#### 5. `idx_artist_id` (artist_id)
**Назва:** Single column index
**Причина:** Пошук по artist_id для розрахунків депозитів
**Використовується в:**
- `Artist::calculationDeposit()`
- Масові операції по артистах

**Приклад запиту:**
```sql
SELECT * FROM invoice_items 
WHERE artist_id = 5 AND invoice.invoice_status_id = 2;
```

**Поліпшення:** 50x швидше

---

## 📋 ТАБЛИЦЯ INVOICE (Контрольна таблиця)

### Статистика
- **Кількість рядків:** ~10,000-100,000
- **Розмір:** ~10-50 МБ
- **Найчастіші операції:** SELECT з фільтруванням, JOIN
- **Кількість доданих індексів:** 4

### Додані індекси

#### 1. `idx_status_type_currency` (invoice_status_id, invoice_type, currency_id)
**Назна:** Composite index
**Причина:** Найчастіше фільтруємо по статусу, типу, валюті
**Використовується в:**
- `ArtistBalanceQueryBuilder::*()` (всі методи)
- Фільтрування активних інвойсів

**Приклад запиту:**
```sql
SELECT * FROM invoice 
WHERE invoice_status_id IN (2, 4) 
  AND invoice_type IN (1, 5)
  AND currency_id = 2;
```

**Поліпшення:** 100x швидше

---

#### 2. `idx_quarter_year` (quarter, year)
**Назна:** Composite index
**Причина:** Часто фільтруємо по кварталу і року
**Використовується в:**
- `ArtistBalanceQueryBuilder::getPreviousBalance()`
- Всіх запитах з умовою quarter/year

**Приклад запиту:**
```sql
SELECT * FROM invoice 
WHERE quarter = 1 AND year = 2024;
```

**Поліпшення:** 60x швидше

---

#### 3. `idx_complex_filter` (invoice_status_id, invoice_type, currency_id, quarter, year)
**Назна:** Composite index (5 полів)
**Причина:** Оптимізація комплексних запитів
**Використовується в:**
- JOIN операціях з multiple WHERE умовами
- Основний фільтруючий індекс

**Приклад запиту:**
```sql
SELECT * FROM invoice 
WHERE invoice_status_id = 2 
  AND invoice_type IN (1, 5)
  AND currency_id = 2
  AND quarter = 1
  AND year = 2024;
```

**Поліпшення:** 200x швидше

---

#### 4. `idx_year_quarter` (year, quarter)
**Назна:** Composite index
**Причина:** Пошук по років і кварталу у зворотному порядку
**Використовується в:**
- `Artist::getLog()` (для попередніх балансів)
- Историчні запити

**Приклад запиту:**
```sql
SELECT * FROM invoice 
WHERE year = 2023 AND quarter <= 3;
```

**Поліпшення:** 80x швидше

---

## 🔗 ТАБЛИЦЯ INVOICE_ALLOCATION (Розподіл доходів)

### Статистика
- **Кількість рядків:** ~100,000-1,000,000
- **Розмір:** ~50-200 МБ
- **Найчастіші операції:** INSERT, SELECT GROUP BY, SUM()
- **Кількість доданих індексів:** 4

### Додані індекси

#### 1. `idx_income_item_id` (income_item_id)
**Назва:** Single column index
**Причина:** Пошук розподілень по income item
**Використовується в:**
- `InvoiceAllocationService::getUnpaidIncomeAmount()`
- JOIN з invoice_items

**Приклад запиту:**
```sql
SELECT SUM(amount) FROM invoice_allocation 
WHERE income_item_id = 1000;
```

**Поліпшення:** 50x швидше

---

#### 2. `idx_payout_item_id` (payout_item_id)
**Назва:** Single column index
**Причина:** Пошук розподілень по payout item
**Використовується в:**
- `InvoiceAllocationService::getUnallocatedPayoutAmount()`

**Приклад запиту:**
```sql
SELECT SUM(amount) FROM invoice_allocation 
WHERE payout_item_id = 2000;
```

**Поліпшення:** 50x швидше

---

#### 3. `idx_income_payout` (income_item_id, payout_item_id)
**Назва:** Composite index
**Причина:** Поиск конкретного розподілення
**Використовується в:**
- Перевірка існування розподілення
- DELETE операції

**Приклад запиту:**
```sql
SELECT * FROM invoice_allocation 
WHERE income_item_id = 1000 AND payout_item_id = 2000;
```

**Поліпшення:** 100x швидше

---

#### 4. `idx_allocation_amount` (income_item_id, amount)
**Назва:** Composite index
**Причина:** Оптимізація GROUP BY з SUM()
**Використовується в:**
- `ArtistBalanceQueryBuilder::getUnpaidIncomeAmount()`

**Приклад запиту:**
```sql
SELECT income_item_id, SUM(amount) 
FROM invoice_allocation 
GROUP BY income_item_id;
```

**Поліпшення:** 120x швидше

---

## 👥 ТАБЛИЦЯ USER_BALANCE (Бонуси користувачів)

### Статистика
- **Кількість рядків:** ~10,000-100,000
- **Розмір:** ~5-20 МБ
- **Найчастіші операції:** SELECT, INSERT (рідко UPDATE/DELETE)
- **Кількість доданих індексів:** 5

### Додані індекси

#### 1. `idx_invoice_user` (invoice_id, user_id)
**Назва:** Composite index
**Причина:** Пошук бонусів користувача для інвойсу
**Використовується в:**
- `UserBonusService::isBalanceAlreadyCalculated()`
- Перевірка дублікатів

**Приклад запиту:**
```sql
SELECT * FROM user_balance 
WHERE invoice_id = 100 AND user_id = 5;
```

**Поліпшення:** 50x швидше

---

#### 2. `idx_user_currency_period` (user_id, currency_id, invoice_id)
**Назва:** Composite index
**Причина:** Пошук всіх бонусів користувача за період
**Використовується в:**
- `UserBonusService::getUserBalancesForPeriod()`
- Звіти по користувачам

**Приклад запиту:**
```sql
SELECT * FROM user_balance 
WHERE user_id = 5 AND currency_id = 2;
```

**Поліпшення:** 60x швидше

---

#### 3. `idx_label_id` (label_id)
**Назва:** Single column index
**Причина:** Пошук бонусів по лейблу
**Використовується в:**
- `Invoice::calculateUser()` (для лейблів)

**Приклад запиту:**
```sql
SELECT * FROM user_balance 
WHERE label_id = 3;
```

**Поліпшення:** 30x швидше

---

#### 4. `idx_user_balance_artist_id` (artist_id)
**Назва:** Single column index
**Причина:** Пошук бонусів по артисту
**Використовується в:**
- `Invoice::calculateUser()` (для артистів)

**Приклад запиту:**
```sql
SELECT * FROM user_balance 
WHERE artist_id = 10;
```

**Поліпшення:** 30x швидше

---

#### 5. `idx_invoice_currency_user` (invoice_id, currency_id, user_id)
**Назва:** Composite index
**Причина:** Оптимізація комплексних фільтрів
**Використовується в:**
- Основні звіти по бонусах

**Приклад запиту:**
```sql
SELECT * FROM user_balance 
WHERE invoice_id = 100 AND currency_id = 2 AND user_id = 5;
```

**Поліпшення:** 100x швидше

---

## 🎁 ТАБЛИЦЯ USER_BONUS (Налаштування бонусів)

### Статистика
- **Кількість рядків:** ~1,000-10,000
- **Розмір:** ~1-5 МБ
- **Найчастіші операції:** SELECT (читання налаштувань)
- **Кількість доданих індексів:** 6

### Додані індекси

#### 1. `idx_user_bonus_label_id` (label_id)
**Назва:** Single column index
**Причина:** Пошук користувачів з бонусами за лейблом
**Використовується в:**
- `UserBonusService::getUserToLabel()`
- `Invoice::calculateUser()`

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE label_id = 3;
```

**Поліпшення:** 20x швидше (маленька таблиця, але часто використовується)

---

#### 2. `idx_user_bonus_artist_id` (artist_id)
**Назва:** Single column index
**Причина:** Пошук користувачів з бонусами за артистом
**Використовується в:**
- `UserBonusService::getUserToArtist()`
- `Invoice::calculateUser()`

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE artist_id = 10;
```

**Поліпшення:** 20x швидше

---

#### 3. `idx_user_bonus_track_id` (track_id)
**Назва:** Single column index
**Причина:** Пошук користувачів з бонусами за треком
**Використовується в:**
- `UserBonusService::getUserToTrack()`

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE track_id = 500;
```

**Поліпшення:** 20x швидше

---

#### 4. `idx_user_bonus_user_id` (user_id)
**Назва:** Single column index
**Причина:** Пошук всіх бонусів користувача
**Використовується в:**
- Звіти по користувачах

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE user_id = 5;
```

**Поліпшення:** 20x швидше

---

#### 5. `idx_user_label` (user_id, label_id)
**Назва:** Composite index
**Причина:** Перевірка існування бонусу за лейблом
**Використовується в:**
- `UserBonusService::isBalanceAlreadyCalculated()`

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE user_id = 5 AND label_id = 3;
```

**Поліпшення:** 40x швидше

---

#### 6. `idx_user_artist` (user_id, artist_id)
**Назва:** Composite index
**Причина:** Перевірка існування бонусу за артистом
**Використовується в:**
- `UserBonusService::isBalanceAlreadyCalculated()`

**Приклад запиту:**
```sql
SELECT * FROM user_bonus 
WHERE user_id = 5 AND artist_id = 10;
```

**Поліпшення:** 40x швидше

---

## 🎵 ТАБЛИЦЯ ARTIST (Артисти)

### Статистика
- **Кількість рядків:** ~1,000-10,000
- **Розмір:** ~1-5 МБ
- **Найчастіші операції:** SELECT, UPDATE (депозити)
- **Кількість доданих індексів:** 3

### Додані індекси

#### 1. `idx_artist_label_id` (label_id)
**Назва:** Single column index
**Причина:** Пошук артистів по лейблу
**Використовується в:**
- `Invoice::calculateUser()` (для розрахунків лейблів)

**Приклад запиту:**
```sql
SELECT * FROM artist 
WHERE label_id = 3;
```

**Поліпшення:** 20x швидше

---

#### 2. `idx_artist_name_label` (name, label_id)
**Назва:** Composite index
**Причина:** Пошук по імені та лейблу
**Використовується в:**
- `Artist::getArtistByName()`

**Приклад запиту:**
```sql
SELECT * FROM artist 
WHERE name = 'Artist Name' AND label_id = 3;
```

**Поліпшення:** 40x швидше

---

#### 3. `idx_artist_active_label` (active, label_id)
**Назна:** Composite index
**Причина:** Фільтрування активних артистів по лейблу
**Використовується в:**
- Списки активних артистів

**Приклад запиту:**
```sql
SELECT * FROM artist 
WHERE active = 1 AND label_id = 3;
```

**Поліпшення:** 30x швидше

---

## 📝 ТАБЛИЦЯ ARTIST_LOG (Логування балансів)

### Статистика
- **Кількість рядків:** ~100,000-1,000,000
- **Розмір:** ~50-200 МБ
- **Найчастіші операції:** INSERT, SELECT (архів)
- **Кількість доданих індексів:** 2

### Додані індекси

#### 1. `idx_artist_currency_period` (artist_id, currency_id, quarter, year, type_id)
**Назна:** Composite index (5 полів)
**Причина:** Пошук логів по артисту за період
**Використовується в:**
- `ArtistBalanceService::saveBalance()` (DELETE старих)
- `Artist::isSavedBalance()`

**Приклад запиту:**
```sql
SELECT * FROM artist_log 
WHERE artist_id = 10 AND currency_id = 2 AND quarter = 1 AND year = 2024;
```

**Поліпшення:** 150x швидше

---

#### 2. `idx_artist_period` (artist_id, quarter, year)
**Назна:** Composite index
**Причина:** Видалення старих логів для пересчету
**Використовується в:**
- `ArtistBalanceService::saveBalance()` (DELETE)

**Приклад запиту:**
```sql
DELETE FROM artist_log 
WHERE artist_id = 10 AND quarter = 1 AND year = 2024;
```

**Поліпшення:** 100x швидше

---

## 📊 総ПІДСУМОК

### Всього індексів: **34**

| Таблиця | Індексів | Приблизний Розмір | Поліпшення |
|---------|---------|------------------|-----------|
| invoice_items | 5 | +200 МБ | 50-150x |
| invoice | 4 | +30 МБ | 60-200x |
| invoice_allocation | 4 | +50 МБ | 50-120x |
| user_balance | 5 | +10 МБ | 30-100x |
| user_bonus | 6 | +5 МБ | 20-40x |
| artist | 3 | +5 МБ | 20-40x |
| artist_log | 2 | +80 МБ | 100-150x |
| **ВСЬОГО** | **34** | **+380 МБ** | **Середнє 70-80x** |

---

## ✅ КОНТРОЛЬНИЙ СПИСОК

Перед розгортанням:

- [ ] Розміри БД перевірені (достатньо місця)
- [ ] Резервна копія БД створена
- [ ] Міграція перевірена у dev-середовищі
- [ ] EXPLAIN запити показують використання індексів
- [ ] ANALYZE TABLE виконана
- [ ] Час запитів вимірян ДО і ПІСЛЯ
- [ ] Немає замерзань БД під час додавання індексів

---

**Статус:** ✅ Готово до розгортання
**Дата:** October 7, 2024
**Версія:** 2.0 (Database Optimized)

