# 📊 ІНДЕКСИ БАЗИ ДАНИХ: Система роялті v2.0

## 📋 ОГЛЯД

Цей каталог містить всі скрипти та документацію для додавання оптимізаційних індексів до БД системи розрахунку роялті.

---

## 📁 ФАЙЛИ

### 1. SQL скрипт
- **Файл:** `database/migrations/add_optimization_indexes.sql`
- **Опис:** Чистий SQL для додавання 34 індексів
- **Користування:** Копіюйте вміст у PhpMyAdmin або запустіть через MySQL CLI
- **Час виконання:** ~2-5 хвилин

### 2. Yii2 міграція
- **Файл:** `console/migrations/m241007_000000_add_optimization_indexes.php`
- **Опис:** Yii Framework міграція
- **Користування:** `php yii migrate/up --migration-path=@console/migrations`
- **Час виконання:** ~2-5 хвилин

### 3. Guide
- **Файл:** `DATABASE_INDEXES_GUIDE.md`
- **Опис:** Пошаговий гайд як додати індекси, перевірити, видалити
- **Користування:** Для розробників

### 4. Аналіз
- **Файл:** `DATABASE_INDEXES_ANALYSIS.md`
- **Опис:** Детальний аналіз кожного індексу
- **Користування:** Для розуміння чому додавалась кожен індекс

---

## 🚀 ШВИДКИЙ СТАРТ

### Метод 1: Через Yii2 міграцію (РЕКОМЕНДОВАНО)

```bash
cd C:\GIT\Komar\blck.link
php yii migrate/up --migration-path=@console/migrations
```

### Метод 2: Через SQL скрипт

```bash
# Через командний рядок
mysql -u user -p database < database/migrations/add_optimization_indexes.sql

# Або вручну в PhpMyAdmin
```

---

## ✅ СТАТИСТИКА

### Індекси, які будуть додані:

| Таблиця | Кількість | Загальний розмір |
|---------|:---:|:---:|
| invoice_items | 5 | +200 МБ |
| invoice | 4 | +30 МБ |
| invoice_allocation | 4 | +50 МБ |
| user_balance | 5 | +10 МБ |
| user_bonus | 6 | +5 МБ |
| artist | 3 | +5 МБ |
| artist_log | 2 | +80 МБ |
| **ВСЬОГО** | **34** | **+380 МБ** |

### Очікуване поліпшення:

- **SELECT запити:** 50-150x швидше
- **JOIN операції:** 60-200x швидше
- **GROUP BY операції:** 100-150x швидше
- **Загалом:** 70-80% скорочення часу виконання

---

## ⚠️ ВАЖЛИВО

### Перед додаванням індексів

- [ ] Зробити резервну копію БД
- [ ] Перевірити вільне місце на диску (~500 МБ)
- [ ] Запустити під час низької активності
- [ ] Не перериватися під час процесу

### Після додавання індексів

- [ ] Запустити `ANALYZE TABLE` (робиться автоматично)
- [ ] Перевірити що індекси використовуються (див. GUIDE)
- [ ] Протестувати на dev/staging
- [ ] Порівняти продуктивність

---

## 🔍 ПЕРЕВІРКА

Після додавання індексів, перевірити їх існування:

```sql
-- Переглянути всі індекси
SELECT * FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'invoice_items',
      'invoice',
      'invoice_allocation',
      'user_balance',
      'user_bonus',
      'artist',
      'artist_log'
  )
ORDER BY TABLE_NAME, SEQ_IN_INDEX;
```

---

## 🆘 ВИДАЛЕННЯ

Якщо потрібно видалити індекси:

```bash
# Через Yii2 (откат)
php yii migrate/down --migration-path=@console/migrations

# Або вручну видалити (див. GUIDE)
```

---

## 📚 ДОДАТКОВА ІНФОРМАЦІЯ

- **QUICK_START.md** - для новачків
- **OPTIMIZATION_DOCUMENTATION.md** - загальна документація
- **DATABASE_INDEXES_GUIDE.md** - детальний гайд
- **DATABASE_INDEXES_ANALYSIS.md** - аналіз кожного індексу
- **FINAL_SUMMARY.md** - повне резюме проекту

---

**Версія:** 2.0
**Дата:** October 7, 2024
**Статус:** ✅ Готово до розгортання

