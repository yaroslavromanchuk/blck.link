# 🎯 НОВЫЙ АУДИТ: Основная логика роялти (КРИТИЧНЫЕ ПРОБЛЕМЫ)

**Дата:** October 7, 2024  
**Фокус:** Основная логика разрахування роялті (Artist, Partner, Features)  
**Статус:** 🔴 КРИТИЧНЫЕ ПРОБЛЕМЫ НАЙДЕНЫ

---

## 🔍 ПРОБЛЕМА #1: `track_to_percentage` НЕ ИСПОЛЬЗУЕТСЯ! 🔴

**Локация:** `ArtistBalanceQueryBuilder::getArtistDirectIncome()` (строка 67-87)

**Текущая логика:**
```sql
SELECT SUM(ii.amount)
FROM invoice_items ii
WHERE ii.artist_id = :artist_id
  AND t.artist_id = ii.artist_id  ← Это только для основного автора!
```

**ПРОБЛЕМА:**
- Система БЕЗ УЧЕТА `track_to_percentage`!
- Если в triangle есть проценты:
  - Artist A: 80%
  - Artist B: 20%
- Система не знает как распределить доход!
- **Результат:** Неправильный расчёт роялти!

**Вопросы:**
1. Как в `invoice_items` хранится информация про фичи?
   - ii.artist_id = A, потом ii.artist_id = B?
   - Или одна запись на всё и потом делим?

2. Где используется `track_to_percentage`?
   - В формировании invoice_items?
   - Вообще используется?

3. Как это должно работать?
   - track.artist_id = основной автор
   - track_to_percentage содержит ВСЕ артистов + проценты
   - invoice_items должен содержать строки для каждого артиста?

---

## 🔍 ПРОБЛЕМА #2: НЕТ РАЗДЕЛЕНИЯ ЛОГИКИ Artist VS Partner! 🔴

**Локация:** Вся система

**ВОПРОС:** Где в коде определяется:
```
ЕСЛИ artist_type = "Partner" ТО:
  Берём проценты из artist.percentage
  Считаем только паблишинг и дистрибуцию

ЕСЛИ artist_type = "Artist" ТО:
  Берём проценты из track_to_percentage
  Считаем всё (авторские, фичи, лейбл)
```

**Поиск показал:** Нет такой логики в `ArtistBalanceQueryBuilder`! 🔴

**Результат:**
- Artist и Partner считаются одинаково
- Это НЕПРАВИЛЬНО!

---

## 🔍 ПРОБЛЕМА #3: `ii.from_artist_id` - ЧТО ЭТО? 🔴

**Локация:** `getLabelIncomeFromArtist()` (строка 117-136)

**Текущая логика:**
```sql
WHERE ii.artist_id = 0
  AND ii.from_artist_id = :artist_id
```

**ВОПРОСЫ:**
1. Что такое `ii.from_artist_id`?
   - Это лейбл артиста?
   - Это партнер (дистрибьютор)?
   - Что именно?

2. Когда `ii.artist_id = 0`?
   - Когда это запись о лейбле?
   - Когда это запись о партнере?
   - Как это определяется?

3. Как это связано с Artist vs Partner?
   - Partner рассчитывается по `ii.from_artist_id`?
   - Или это что-то другое?

---

## 📊 ДИАГРАММА ПОТОКА ДАННЫХ (ОЖИДАЕМАЯ)

```
1. ЗАГРУЗКА ОТЧЕТА
   └─ Содержит income за потоки (от агрегатора)

2. ФОРМИРОВАНИЕ INVOICE_ITEMS
   ├─ Для каждого трека в отчете
   ├─ Получить track_to_percentage (все артисты трека)
   ├─ Для каждого артиста в track_to_percentage:
   │  └─ Создать ii.artist_id = этот артист
   │  └─ ii.amount = доход * его процент
   └─ Если есть лейбл:
      └─ Создать отдельную запись ii.artist_id = 0, ii.from_artist_id = лейбл

3. РАСЧЕТ РОЯЛТИ
   ├─ Для Artist:
   │  └─ getArtistDirectIncome() = свои треки
   │  └─ getArtistFeatureIncome() = фичи в других треках
   │  └─ getLabelIncomeFromArtist() = доля лейбла
   └─ Для Partner:
      └─ Просто artist.percentage от всех его доходов
```

**ВОПРОС:** Это то как работает система сейчас?

---

## ✅ ЧТО НУЖНО ПРОВЕРИТЬ

### 1. Таблица `invoice_items` - КАК ХРАНЯТСЯ ДАННЫЕ?

```sql
-- Пример трека с фичами:
-- Track 1: Artist A (автор 80%), Artist B (фич 20%)

SELECT * FROM invoice_items
WHERE track_id = 1 AND invoice_id = 100;

-- Результаты могут быть:
-- Вариант 1: 2 строки
--   artist_id=A, amount=800
--   artist_id=B, amount=200
-- 
-- Вариант 2: 1 строка
--   artist_id=A, amount=1000 (потом делим по track_to_percentage)
```

### 2. Таблица `track_to_percentage` - КАК ИСПОЛЬЗУЕТСЯ?

```sql
SELECT * FROM track_to_percentage WHERE track_id = 1;
-- Должны быть строки типа:
-- artist_id=1, percentage=80, ownership_type=1 (автор)
-- artist_id=2, percentage=20, ownership_type=1 (фич)
```

### 3. Artist vs Partner - КАК ОПРЕДЕЛЯЕТСЯ?

```sql
SELECT id, name, artist_type_id, percentage FROM artist LIMIT 5;

-- Нужно понять:
-- Какой artist_type_id = Partner?
-- Где используется artist.percentage?
```

---

## 🔴 КРИТИЧНЫЕ ПРОБЛЕМЫ (ИТОГО)

| # | Проблема | Вплив | Критичность |
|---|----------|:-----:|:-----------:|
| 1 | track_to_percentage НЕ используется | Неправильный расчёт фитов | 🔴 КРИТИЧНО |
| 2 | Нет разделения Artist vs Partner | Неправильный расчёт Partner | 🔴 КРИТИЧНО |
| 3 | ii.from_artist_id неясна | Непонятна логика лейбла | 🟡 ВЫСОКИЙ |
| 4 | Отсутствует разделение паблишинга/дистрибуции | Неправильный расчёт Partner | 🟡 ВЫСОКИЙ |

---

## 📋 ПЛАН АУДИТА

### Этап 1: ПОНИМАНИЕ СТРУКТУРЫ ДАННЫХ (1 час)

```sql
-- 1. Посмотреть как хранятся фичи:
SELECT COUNT(*), artist_id, from_artist_id
FROM invoice_items
WHERE track_id IN (SELECT id FROM track WHERE ... has features)
GROUP BY artist_id, from_artist_id;

-- 2. Проверить track_to_percentage:
SELECT COUNT(*)
FROM track_to_percentage
WHERE artist_id > 0;

-- 3. Проверить использование artist_type_id:
SELECT DISTINCT artist_type_id, COUNT(*) FROM artist GROUP BY artist_type_id;
```

### Этап 2: АНАЛИЗ КОДА (2 часа)

Вопросы:
- Где формируются `invoice_items`? (какой код это делает)
- Используется ли `track_to_percentage` при формировании?
- Есть ли отдельная логика для Partner?
- Как определяется Partner (по artist_type_id)?

### Этап 3: ВЫЯВЛЕНИЕ ОШИБОК (1 час)

На основе этапа 1-2:
- Правильно ли распределяются проценты?
- Правильно ли считаются фичи?
- Правильно ли считаются Partner?

---

**Статус:** 🔴 КРИТИЧНЫЕ ПРОБЛЕМЫ В ОСНОВНОЙ ЛОГИКЕ

**Рекомендация:** Нужно провести глубокий аудит основной логики, а не тратить время на бонусы сотрудников!


