-- ============================================================
-- 🔍 СКРИПТ ДІАГНОСТИКИ: Перевірка логіки розрахунків роялті
-- ============================================================
-- Цей скрипт допоможе знайти проблеми у розрахунках
-- Виконати у MySQL/PhpMyAdmin
-- ============================================================

-- 1️⃣ ПЕРЕВІРКА: Дублювання бонусів користувачів
-- ============================================================
SELECT
    ub.invoice_id,
    ub.user_id,
    CONCAT('Label:', ub.label_id, ' Artist:', ub.artist_id) as target,
    COUNT(*) as duplicate_count,
    SUM(ub.amount) as total_amount,
    GROUP_CONCAT(ub.balance_id ORDER BY ub.balance_id) as balance_ids
FROM `user_balance` ub
GROUP BY ub.invoice_id, ub.user_id, ub.label_id, ub.artist_id
HAVING COUNT(*) > 1
ORDER BY ub.invoice_id DESC
LIMIT 50;

-- Результат:
-- Якщо є рядки → ПРОБЛЕМА! Бонусы дублюються

---

-- 2️⃣ ПЕРЕВІРКА: Сума бонусів перевищує доход інвойсу
-- ============================================================
SELECT
    i.invoice_id,
    i.total as invoice_total,
    ROUND(SUM(ub.amount), 2) as bonuses_sum,
    ROUND(SUM(ub.amount) - i.total, 2) as difference,
    CASE
        WHEN SUM(ub.amount) > i.total THEN '❌ БОНУСЫ > ДОХОД'
        WHEN SUM(ub.amount) = i.total THEN '✅ Коректно'
        ELSE '⚠️ Неповні бонусы'
    END as status
FROM `invoice` i
LEFT JOIN `user_balance` ub ON ub.invoice_id = i.invoice_id
WHERE i.invoice_status_id = 2
  AND i.invoice_type = 1  -- Лише доходи
GROUP BY i.invoice_id
HAVING SUM(ub.amount) > i.total
ORDER BY i.invoice_id DESC
LIMIT 50;

-- Результат:
-- Якщо бонусы > доход → ПРОБЛЕМА!

---

-- 3️⃣ ПЕРЕВІРКА: Відсотки користувачів перевищують 100%
-- ============================================================
SELECT
    ub.invoice_id,
    ub.artist_id,
    ub.label_id,
    GROUP_CONCAT(
        CONCAT(u.username, ':', ub.percentage, '%')
        SEPARATOR ', '
    ) as users_and_percentages,
    SUM(ub.percentage) as total_percentage,
    CASE
        WHEN SUM(ub.percentage) > 100 THEN '❌ > 100%'
        WHEN SUM(ub.percentage) = 100 THEN '✅ 100%'
        ELSE '⚠️ < 100%'
    END as status
FROM `user_balance` ub
LEFT JOIN `user` u ON u.id = ub.user_id
WHERE ub.invoice_id > 0
GROUP BY ub.invoice_id, ub.artist_id, ub.label_id
HAVING SUM(ub.percentage) != 100 OR COUNT(*) > 1
ORDER BY ub.invoice_id DESC
LIMIT 50;

-- Результат:
-- Якщо сума != 100% → ПРОБЛЕМА!

---

-- 4️⃣ ПЕРЕВІРКА: Invoice Items без треків
-- ============================================================
SELECT
    ii.id,
    ii.invoice_id,
    ii.artist_id,
    ii.track_id,
    ii.amount,
    CASE
        WHEN ii.track_id IS NULL THEN '❌ Нема track_id'
        WHEN t.id IS NULL THEN '❌ Трек не знайдено'
        ELSE '✅ OK'
    END as status
FROM `invoice_items` ii
LEFT JOIN `track` t ON t.id = ii.track_id
WHERE ii.amount > 0
  AND (ii.track_id IS NULL OR t.id IS NULL)
ORDER BY ii.invoice_id DESC
LIMIT 50;

-- Результат:
-- Якщо є рядки без треків → дохід може бути недораховано!

---

-- 5️⃣ ПЕРЕВІРКА: Артисти без відсотків у своїх треках
-- ============================================================
SELECT
    ii.artist_id,
    ii.track_id,
    t.name as track_name,
    ii.amount,
    COUNT(tp.id) as percentage_count,
    SUM(tp.percentage) as total_percentage
FROM `invoice_items` ii
INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
LEFT JOIN `track` t ON t.id = ii.track_id
LEFT JOIN `track_to_percentage` tp ON tp.track_id = ii.track_id
    AND tp.artist_id = ii.artist_id
WHERE i.invoice_status_id = 2
  AND ii.artist_id > 0
  AND ii.track_id > 0
GROUP BY ii.artist_id, ii.track_id
HAVING COUNT(tp.id) = 0 OR SUM(tp.percentage) IS NULL
ORDER BY ii.amount DESC
LIMIT 50;

-- Результат:
-- Якщо є артисти без відсотків → це можуть бути помилки у даних!

---

-- 6️⃣ ПЕРЕВІРКА: Прямий дохід vs дохід з фітів
-- ============================================================
SELECT
    al.artist_id,
    al.quarter,
    al.year,
    al.currency_id,
    al2.sum as direct_income,
    al3.sum as feature_income,
    al4.sum as total_income,
    CASE
        WHEN (IFNULL(al2.sum, 0) + IFNULL(al3.sum, 0)) != IFNULL(al4.sum, 0)
        THEN '❌ Сума не співпадає'
        ELSE '✅ OK'
    END as status
FROM `artist_log` al
LEFT JOIN `artist_log` al2 ON al2.artist_id = al.artist_id
    AND al2.quarter = al.quarter
    AND al2.year = al.year
    AND al2.currency_id = al.currency_id
    AND al2.type_id = 6  -- Прямий дохід
LEFT JOIN `artist_log` al3 ON al3.artist_id = al.artist_id
    AND al3.quarter = al.quarter
    AND al3.year = al.year
    AND al3.currency_id = al.currency_id
    AND al3.type_id = 7  -- Дохід з фітів
LEFT JOIN `artist_log` al4 ON al4.artist_id = al.artist_id
    AND al4.quarter = al.quarter
    AND al4.year = al.year
    AND al4.currency_id = al.currency_id
    AND al4.type_id = 5  -- Загальний дохід
WHERE al.type_id = 6
  AND (IFNULL(al2.sum, 0) + IFNULL(al3.sum, 0)) != IFNULL(al4.sum, 0)
ORDER BY al.artist_id DESC
LIMIT 50;

-- Результат:
-- Якщо статус = ❌ → логіка розрахунку неправильна!

---

-- 7️⃣ ПЕРЕВІРКА: Бонусы які не належать інвойсу
-- ============================================================
SELECT
    ub.balance_id,
    ub.invoice_id,
    ub.user_id,
    ub.amount,
    i.total as invoice_total,
    CASE
        WHEN i.invoice_id IS NULL THEN '❌ Інвойс не знайдено'
        ELSE '✅ OK'
    END as status
FROM `user_balance` ub
LEFT JOIN `invoice` i ON i.invoice_id = ub.invoice_id
WHERE i.invoice_id IS NULL
ORDER BY ub.invoice_id DESC
LIMIT 50;

-- Результат:
-- Якщо є orphaned бонусы → це помилки!

---

-- 8️⃣ ПЕРЕВІРКА: Видавничі права не розраховуються
-- ============================================================
SELECT
    tp.id,
    tp.track_id,
    t.name as track_name,
    ot.name as ownership_type_name,
    tp.artist_id,
    a.name as artist_name,
    tp.percentage,
    CASE
        WHEN ot.id IS NULL THEN '⚠️ Тип власності не вказан'
        WHEN o.id IS NULL THEN '❌ Власність не знайдена'
        ELSE '✅ OK'
    END as status
FROM `track_to_percentage` tp
INNER JOIN `track` t ON t.id = tp.track_id
INNER JOIN `artist` a ON a.id = tp.artist_id
LEFT JOIN `ownership_type` ot ON ot.id = tp.ownership_type
LEFT JOIN `ownership` o ON o.id = ot.ownership_id
ORDER BY tp.track_id DESC
LIMIT 50;

-- Результат:
-- Якщо є записи зі статусом ⚠️ або ❌ → видавничі права не налаштовані правильно!

---

-- ============================================================
-- 🔧 РЕЗЮМЕ ДІАГНОСТИКИ
-- ============================================================
-- Запустіть усі 8 запитів вище щоб отримати повну картину
--
-- Якщо запит 1 повертає результати → КРИТИЧНО!
-- Якщо запит 2 повертає результати → КРИТИЧНО!
-- Якщо запит 3 повертає результати → ПОМИЛКА!
-- Якщо запит 4 повертає результати → ПОПЕРЕДЖЕННЯ!
-- Якщо запит 5 повертає результати → ПОМИЛКА!
-- Якщо запит 6 повертає результати → ПОМИЛКА!
-- Якщо запит 7 повертає результати → ПОМИЛКА!
-- Якщо запит 8 повертає результати → ПОПЕРЕДЖЕННЯ!
-- ============================================================

