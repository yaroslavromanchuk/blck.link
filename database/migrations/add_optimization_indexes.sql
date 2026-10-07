-- ============================================================
-- 📊 ОПТИМІЗАЦІЯ ІНДЕКСІВ ДЛЯ СИСТЕМИ РОЯЛТІ v2.0
-- ============================================================
-- Дата: October 7, 2024
-- Мета: Покращити продуктивність найчастіше використовуваних запитів
--
-- ВАЖЛИВО: Виконати у правильному порядку! Індекси можуть займати місце
-- ============================================================

-- ============================================================
-- 1️⃣ ТАБЛИЦЯ: invoice_items
-- ============================================================
-- Оптимізація: N+1 запити для батче-операцій

-- Індекс для пошуку по invoice_id і artist_id (часто фільтруємо)
ALTER TABLE `invoice_items`
ADD INDEX `idx_invoice_artist` (`invoice_id`, `artist_id`);

-- Індекс для пошуку по invoice_id і from_artist_id (для лейблів)
ALTER TABLE `invoice_items`
ADD INDEX `idx_invoice_from_artist` (`invoice_id`, `from_artist_id`);

-- Індекс для пошуку по amount (часто перевіряємо на 0 і >0)
ALTER TABLE `invoice_items`
ADD INDEX `idx_amount` (`amount`);

-- Складний індекс для оптимізації getInvoiceReportDataGroupArtist()
ALTER TABLE `invoice_items`
ADD INDEX `idx_invoice_artist_from_artist` (`invoice_id`, `artist_id`, `from_artist_id`, `amount`);

-- Індекс для пошуку по artist_id (для розрахунків депозитів)
ALTER TABLE `invoice_items`
ADD INDEX `idx_artist_id` (`artist_id`);

-- ============================================================
-- 2️⃣ ТАБЛИЦЯ: invoice
-- ============================================================
-- Оптимізація: Батче-запити в ArtistBalanceQueryBuilder

-- Індекс для фільтрування по типу, статусу, валюті, кварталу, року
ALTER TABLE `invoice`
ADD INDEX `idx_status_type_currency` (`invoice_status_id`, `invoice_type`, `currency_id`);

-- Індекс для фільтрування по кварталу і року (часто фільтруємо)
ALTER TABLE `invoice`
ADD INDEX `idx_quarter_year` (`quarter`, `year`);

-- Складний індекс для пошуку по статусу, типу, валюті, кварталу, року
ALTER TABLE `invoice`
ADD INDEX `idx_complex_filter` (
    `invoice_status_id`,
    `invoice_type`,
    `currency_id`,
    `quarter`,
    `year`
);

-- Індекс для пошуку по year, quarter (для попередніх балансів)
ALTER TABLE `invoice`
ADD INDEX `idx_year_quarter` (`year`, `quarter`);

-- ============================================================
-- 3️⃣ ТАБЛИЦЯ: invoice_allocation
-- ============================================================
-- Оптимізація: Розподіл доходів і виплат

-- Індекс для пошуку розподілення по income item
ALTER TABLE `invoice_allocation`
ADD INDEX `idx_income_item_id` (`income_item_id`);

-- Індекс для пошуку розподілення по payout item
ALTER TABLE `invoice_allocation`
ADD INDEX `idx_payout_item_id` (`payout_item_id`);

-- Складний індекс для пошуку по обом полям
ALTER TABLE `invoice_allocation`
ADD INDEX `idx_income_payout` (`income_item_id`, `payout_item_id`);

-- Індекс для оптимізації GROUP BY операцій
ALTER TABLE `invoice_allocation`
ADD INDEX `idx_allocation_amount` (`income_item_id`, `amount`);

-- ============================================================
-- 4️⃣ ТАБЛИЦЯ: user_balance
-- ============================================================
-- Оптимізація: Розрахунок бонусів користувачів

-- Індекс для пошуку балансу по інвойсу та користувачу
ALTER TABLE `user_balance`
ADD INDEX `idx_invoice_user` (`invoice_id`, `user_id`);

-- Індекс для пошуку по користувачу, валюті, період
ALTER TABLE `user_balance`
ADD INDEX `idx_user_currency_period` (`user_id`, `currency_id`, `invoice_id`);

-- Індекс для пошуку по лейблу
ALTER TABLE `user_balance`
ADD INDEX `idx_label_id` (`label_id`);

-- Індекс для пошуку по артисту
ALTER TABLE `user_balance`
ADD INDEX `idx_artist_id` (`artist_id`);

-- Складний індекс для getBalancesForInvoice()
ALTER TABLE `user_balance`
ADD INDEX `idx_invoice_currency_user` (`invoice_id`, `currency_id`, `user_id`);

-- ============================================================
-- 5️⃣ ТАБЛИЦЯ: user_bonus
-- ============================================================
-- Оптимізація: Управління бонусами користувачів

-- Індекс для пошуку по лейблу
ALTER TABLE `user_bonus`
ADD INDEX `idx_label_id` (`label_id`);

-- Індекс для пошуку по артисту
ALTER TABLE `user_bonus`
ADD INDEX `idx_artist_id` (`artist_id`);

-- Індекс для пошуку по треку
ALTER TABLE `user_bonus`
ADD INDEX `idx_track_id` (`track_id`);

-- Індекс для пошуку по користувачу
ALTER TABLE `user_bonus`
ADD INDEX `idx_user_id` (`user_id`);

-- Складний індекс для визначення існування бонусу
ALTER TABLE `user_bonus`
ADD INDEX `idx_user_label` (`user_id`, `label_id`);

ALTER TABLE `user_bonus`
ADD INDEX `idx_user_artist` (`user_id`, `artist_id`);

-- ============================================================
-- 6️⃣ ТАБЛИЦЯ: artist
-- ============================================================
-- Оптимізація: Пошук артистів по лейблу

-- Індекс для пошуку артистів по лейблу
ALTER TABLE `artist`
ADD INDEX `idx_label_id` (`label_id`);

-- Індекс для пошуку по імені (для getArtistByName())
ALTER TABLE `artist`
ADD INDEX `idx_name_label` (`name`, `label_id`);

-- Індекс для фільтрування активних артистів
ALTER TABLE `artist`
ADD INDEX `idx_active_label` (`active`, `label_id`);

-- ============================================================
-- 7️⃣ ТАБЛИЦЯ: artist_log
-- ============================================================
-- Оптимізація: Логування балансів

-- Індекс для пошуку логів по артисту, валюті, періоду
ALTER TABLE `artist_log`
ADD INDEX `idx_artist_currency_period` (
    `artist_id`,
    `currency_id`,
    `quarter`,
    `year`,
    `type_id`
);

-- Індекс для видалення старих логів
ALTER TABLE `artist_log`
ADD INDEX `idx_artist_period` (`artist_id`, `quarter`, `year`);

-- ============================================================
-- 8️⃣ ТАБЛИЦЯ: v_income_balance (представлення)
-- ============================================================
-- ПРИМІТКА: Якщо це представлення, індекси додаються до базових таблиць

-- Базові індекси вже добавлені вище для invoice_items та invoice

-- ============================================================
-- 9️⃣ ТАБЛИЦЯ: v_payout_balance (представлення)
-- ============================================================
-- ПРИМІТКА: Якщо це представлення, індекси додаються до базових таблиць

-- Базові індекси вже добавлені вище для invoice_items та invoice

-- ============================================================
-- 📊 СТАТИСТИКА ІНДЕКСІВ
-- ============================================================
-- Виконайте після додавання індексів:
--
-- ANALYZE TABLE `invoice_items`;
-- ANALYZE TABLE `invoice`;
-- ANALYZE TABLE `invoice_allocation`;
-- ANALYZE TABLE `user_balance`;
-- ANALYZE TABLE `user_bonus`;
-- ANALYZE TABLE `artist`;
-- ANALYZE TABLE `artist_log`;

-- ============================================================
-- 🔍 ПЕРЕВІРКА ІНДЕКСІВ
-- ============================================================
-- Для перевірки створених індексів:
--
-- SELECT * FROM INFORMATION_SCHEMA.STATISTICS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME IN (
--       'invoice_items',
--       'invoice',
--       'invoice_allocation',
--       'user_balance',
--       'user_bonus',
--       'artist',
--       'artist_log'
--   )
-- ORDER BY TABLE_NAME, SEQ_IN_INDEX;

-- ============================================================
-- ⚠️ ВИДАЛЕННЯ ІНДЕКСІВ (якщо потрібно откатити)
-- ============================================================
-- Якщо виникнуть проблеми, можна видалити індекси:
--
-- ALTER TABLE `invoice_items` DROP INDEX `idx_invoice_artist`;
-- ALTER TABLE `invoice_items` DROP INDEX `idx_invoice_from_artist`;
-- ALTER TABLE `invoice_items` DROP INDEX `idx_amount`;
-- ALTER TABLE `invoice_items` DROP INDEX `idx_invoice_artist_from_artist`;
-- ALTER TABLE `invoice_items` DROP INDEX `idx_artist_id`;
--
-- ... та інші

-- ============================================================
-- 📝 ПРИМІТКИ
-- ============================================================
--
-- 1. Індекси ПОКРАЩУЮТЬ поточність SELECT, але СПОВІЛЬНЮЮТЬ INSERT/UPDATE/DELETE
-- 2. Рекомендується ANALYZE TABLE після додавання індексів
-- 3. Використовуйте EXPLAIN для аналізу запитів
-- 4. Периодично перевіряйте використання індексів (STATISTICS)
-- 5. Видаляйте невикористовувані індекси (займають місце)
--
-- ============================================================

