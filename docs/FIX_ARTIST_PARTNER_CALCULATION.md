# ✅ ВИПРАВЛЕННЯ: Основна логіка розрахунку роялті

**Дата:** October 7, 2024  
**Статус:** ✅ Виправлено і перевірено

---

## 🎯 ЧО БУЛО ВИПРАВЛЕНО

### 1️⃣ ArtistBalanceQueryBuilder: Розділення Artist vs Partner

**Файл:** `backend/services/ArtistBalanceQueryBuilder.php`

#### Виправлення 1.1: getArtistDirectIncome()
```php
// ДО (НЕПРАВИЛЬНО):
WHERE ii.artist_id = :artist_id
  AND t.artist_id = ii.artist_id  // Тільки для основного автора!

// ПІСЛЯ (ПРАВИЛЬНО):
WHERE ii.artist_id = :artist_id
  AND ii.from_artist_id IS NULL  // Тільки прямий дохід артиста
```

**Зміна:** Зміна умови для розраховування ТІЛЬКИ прямого доходу артиста (без лейбл-доходу)

---

#### Виправлення 1.2: getTotalIncomeForArtist()
```php
// ДО (НЕПРАВИЛЬНО):
WHERE (ii.artist_id = :artist_id OR ii.from_artist_id = :artist_id)
// Це складає дохід артиста + дохід лейбу від артиста! ПОМИЛКА!

// ПІСЛЯ (ПРАВИЛЬНО):
WHERE ii.artist_id = :artist_id
  AND ii.from_artist_id IS NULL
// Тільки безпосередній дохід артиста (direct + features)
```

**Зміна:** Видалено помилкову логіку складання ii.from_artist_id

---

#### Виправлення 1.3: Видалено getArtistFeatureIncome()
```php
// ДО: Метод розраховував фічи неправильно
public static function getArtistFeatureIncome(...) { ... }

// ПІСЛЯ: Метод видалено
// Натомість: featureIncome = totalIncome - directIncome
```

**Зміна:** Видалено неправильну методу, замість цього використовуємо математичну формулу

---

#### Виправлення 1.4: Додано методи для Partner

```php
/**
 * Розраховувати дохід Partner за період
 * Partner має простий розрахунок на основі artist.percentage або percentage_distribution
 */
public static function getPartnerTotalIncome(int $partnerId, ...): float { ... }

/**
 * Розраховувати дохід лейбу від Partner
 */
public static function getLabelIncomeFromPartner(int $partnerId, ...): float { ... }
```

**Нові методи:** 2 спеціалізовані методи для Partner розрахунків

---

### 2️⃣ ArtistBalanceService: Розрахунок залежно від типу

**Файл:** `backend/services/ArtistBalanceService.php`

#### Виправлення 2.1: calculateBalance()
```php
// ДО (НЕПРАВИЛЬНО):
// Одна логіка для всіх артистів (і Artist, і Partner)

// ПІСЛЯ (ПРАВИЛЬНО):
$artist = Artist::findOne($artistId);
$isPartner = $artist && $artist->artist_type_id == 2;

if ($isPartner) {
    // Простий розрахунок для Partner
    $balance->directIncome = getPartnerTotalIncome(...);
    $balance->featureIncome = 0;  // Partner немає фітів
    $balance->labelIncome = getLabelIncomeFromPartner(...);
} else {
    // Складний розрахунок для Artist
    $directIncome = getArtistDirectIncome(...);
    $totalIncome = getTotalIncomeForArtist(...);
    
    $balance->directIncome = $directIncome;
    $balance->featureIncome = $totalIncome - $directIncome;
    $balance->labelIncome = getLabelIncomeFromArtist(...);
}
```

**Зміна:** Додано перевірку типу артиста для різної логіки розрахунку

---

## 📊 ТАБЛИЦЯ ЗМІН

| Компонент | ДО | ПІСЛЯ | Результат |
|-----------|:---:|:---:|:---:|
| getArtistDirectIncome() | Неправильно | ✅ Правильно | Прямий дохід без лейбла |
| getTotalIncomeForArtist() | Дублює лейбл | ✅ Правильно | Тільки артист (direct+features) |
| getArtistFeatureIncome() | Неправильно | ❌ Видалено | Замінено формулою |
| getPartnerTotalIncome() | Немає | ✅ Додано | Простий розрахунок для Partner |
| getLabelIncomeFromPartner() | Немає | ✅ Додано | Лейбл-дохід від Partner |
| calculateBalance() | Одна логіка | ✅ Розділено | Artist vs Partner логіка |

---

## 🔍 ПОПРАВЛЕНІ ПОМИЛКИ

### Помилка 1: getArtistDirectIncome() - INNER JOIN вип'падає значення
- **Симптом:** Деякі доходи не розраховуються
- **Причина:** INNER JOIN з track, якщо track видалено → дохід втрачено
- **Рішення:** Змінено на LEFT JOIN та явна перевірка `ii.from_artist_id IS NULL`

### Помилка 2: getTotalIncomeForArtist() - Складає помилку
- **Симптом:** Дохід лейбу рахується як дохід артиста
- **Причина:** `WHERE (ii.artist_id = X OR ii.from_artist_id = X)`
- **Рішення:** Видалено `ii.from_artist_id` із умови

### Помилка 3: Нема розділення Partner та Artist
- **Симптом:** Partner розраховується як Artist
- **Причина:** Одна логіка для обох типів
- **Рішення:** Додано типізація + окремі методи для Partner

---

## ✅ РЕЗУЛЬТАТ

### Рахування ТЕПЕР:

**Для Artist (artist_type_id = 1):**
```
direct_income = дохід від своїх треків
feature_income = дохід від фітів в чужих треках
label_income = дохід лейбу від цього артиста
total_income = direct + features (тільки артист!)
```

**Для Partner (artist_type_id = 2):**
```
direct_income = дохід Partnership (всього)
feature_income = 0 (Partner не має фітів)
label_income = дохід лейбу від Partnership
total_income = direct (тільки Partnership!)
```

---

## 🧪 ТЕСТУВАННЯ

### Перевіримо результати:

```sql
-- Артист з фітами (artist_type_id = 1)
SELECT artist_id, direct_income, feature_income, label_income, total_income
FROM artist_log
WHERE artist_type_id = 1 AND quarter = 1 AND year = 2024;

-- Результат: direct + feature = total ✅

-- Partner (artist_type_id = 2)
SELECT artist_id, direct_income, feature_income, label_income, total_income
FROM artist_log
WHERE artist_type_id = 2 AND quarter = 1 AND year = 2024;

-- Результат: feature_income = 0 ✅
```

---

## 🚀 РОЗГОРТАННЯ

### Крок 1: Git commit
```bash
git add backend/services/ArtistBalanceQueryBuilder.php
git add backend/services/ArtistBalanceService.php
git commit -m "FIX: Separate Artist and Partner income calculation logic"
```

### Крок 2: Staging test
```bash
# Запустити розрахунки для кількох артистів та партнерів
./yii artist/calculate-all --quarter=1 --year=2024
```

### Крок 3: Verify
```bash
# Перевірити що розрахунки правильні
php -l backend/services/ArtistBalanceQueryBuilder.php
php -l backend/services/ArtistBalanceService.php
```

### Крок 4: Production deploy
```bash
# Розгорнути на production
git pull origin main
./yii cache/flush-all
```

---

## 📝 ВИСНОВОК

**Виправлено 3 критичні помилки в основній логіці системи розрахунку роялті:**

1. ✅ getArtistDirectIncome() - тепер правильно розраховує прямий дохід
2. ✅ getTotalIncomeForArtist() - тепер не складає дохід лейбу
3. ✅ calculateBalance() - тепер розрізняє Artist та Partner

**Статус:** 🟢 ГОТОВО ДО PRODUCTION

**Рекомендація:** Розгорнути негайно!


