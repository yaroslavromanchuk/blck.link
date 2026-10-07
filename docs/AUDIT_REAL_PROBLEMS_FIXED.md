# 🔴 КРИТИЧНИЙ АУДИТ: Реальна архітектура розрахунку роялті

**Дата:** October 7, 2024  
**Статус:** ✅ Розуміємо правильно архітектуру

---

## ✅ КАК НАСПРАВДІ ПРАЦЮЄ СИСТЕМА

### 1. Формування invoice_items (actionGenerateInvoice)

```
Для кожного треку:
  └─ Викликаємо Track::getCalculation()
  └─ Отримуємо масив з 2+ строками:
     ├─ Строка 1: artist_id = артист, amount = його доля
     └─ Строка 2: artist_id = 0, from_artist_id = артист, amount = дохід лейбу
```

### 2. Track::getCalculation() (рядок 410)

**Для Partner (isClient()):**
```php
if ($this->artist->isClient()) {  // Partner = Client
    $percentageArtist = $this->artist->percentage;  // Один відсоток!
    // або $this->artist->percentage_distribution для дистрибуції
    
    // Результат:
    // Строка 1: artist_id = partner_id, amount = partner_share
    // Строка 2: artist_id = 0, from_artist_id = partner_id, amount = label_share
}
```

**Для Artist:**
```php
// Беріть з track_to_percentage для КОЖНОГО артиста
$data = Percentage::find()
    ->where(['track_id' => $this->id, 'aggregator_id' => $aggregator_id])
    ->groupBy(['artist_id'])
    ->all();

// Результат:
// Для Artist A (80%):
//   Строка 1: artist_id = A, amount = його доля
//   Строка 2: artist_id = 0, from_artist_id = A, amount = його частка лейбу
// Для Artist B (20% - фіч):
//   Строка 1: artist_id = B, amount = його доля
//   Строка 2: artist_id = 0, from_artist_id = B, amount = його частка лейбу
```

---

## 🔴 КРИТИЧНІ ПРОБЛЕМИ

### Проблема #1: ArtistBalanceQueryBuilder НЕ РОЗРІЗНЯЄ Artist vs Partner! 🔴

**Локація:** `backend/services/ArtistBalanceQueryBuilder.php`

**Проблема:**
```php
// Код для getArtistDirectIncome():
SELECT SUM(ii.amount)
WHERE ii.artist_id = :artist_id
  AND t.artist_id = ii.artist_id  // Це лише для автора!

// Це працює для Artist, але:
// - Не розраховує правильно для Partner!
// - Partner повинен мати просту логіку (одна сума)
// - Artist повинен мати складну логіку (фічи, авторські, видавничі)
```

**Вплив:**
- ❌ Partner розраховується НЕПРАВИЛЬНО (як Artist)
- ❌ Artist розраховується НЕПРАВИЛЬНО (дублюється дохід від фітів)

**Приклад помилки:**
```
Трек: Artist A (80%), Artist B (фіч 20%)
Від Partner: паблішинг 10%

ЯК ПОВИННО БУТИ:
- Artist A: отримує 80% від його лейбл-частки
- Artist B: отримує 20% від його лейбл-частки
- Partner: отримує 10% от усього

ЯК НАСПРАВДІ (з помилкою):
- Artist A: складний розрахунок (можливо дублюється)
- Artist B: розраховується як Artist, а не Partner
- Partner: розраховується як Artist!
```

---

### Проблема #2: Нема РОЗДІЛЕННЯ у ArtistBalanceQueryBuilder! 🔴

**Локація:** `ArtistBalanceQueryBuilder` - ВСІ методи

**Проблема:**
```php
// Немає методу для Partner!
// Немає методу для розрахунку паблішингу vs дистрибуції!

// Методи що є:
- getArtistDirectIncome()    // Тільки для Artist!
- getArtistFeatureIncome()   // Тільки для Artist!
- getLabelIncomeFromArtist() // Для обох, але неправильно!
- getTotalIncomeForArtist()  // Для обох, але неправильно!

// Методи що потребуються:
- getPartnerIncomeByType()   // для паблішингу vs дистрибуції
- getArtistIncomeByType()    // для Artist
```

**Вплив:**
- ❌ Partner та Artist розраховуються ОДНАКОВО
- ❌ Це НЕПРАВИЛЬНО!

---

### Проблема #3: Логіка в ArtistBalanceQueryBuilder НЕ ВІДПОВІДАЄ Track::getCalculation()! 🔴

**Track::getCalculation() вирішує:**
```php
// Для КОЖНОГО артиста створюємо 2 строки:
1. Прямий дохід артисту
2. Дохід лейбу від артиста (ii.artist_id = 0, ii.from_artist_id = артист)
```

**ArtistBalanceQueryBuilder робить:**
```php
// getTotalIncomeForArtist():
WHERE (ii.artist_id = :artist_id OR ii.from_artist_id = :artist_id)

// Це складає дві строки в одну!
// Результат: подвійний дохід або неправильні цифри!
```

**Приклад:**
```
Track з доходом 100 UAH:
Артист A (80%), Артист B (фіч 20%)

Track::getCalculation() створює:
- ii.artist_id=A, amount=80
- ii.artist_id=0, from_artist_id=A, amount=20
- ii.artist_id=B, amount=20
- ii.artist_id=0, from_artist_id=B, amount=80

ArtistBalanceQueryBuilder::getTotalIncomeForArtist(A) робить:
SELECT SUM(ii.amount) WHERE ii.artist_id=A OR ii.from_artist_id=A
= 80 + 20 = 100 (НЕПРАВИЛЬНО! Це включає лейбл-дохід від інших!)
```

---

## ✅ ЧТО ПОТРЕБУЄ ВИПРАВЛЕННЯ

### Виправлення #1: Розділити Artist та Partner логіку

```php
// У ArtistBalanceQueryBuilder потрібно додати:

// Для Partner:
public static function getPartnerIncomeByType(int $partnerId, int $currencyId, int $quarter, int $year): array
{
    // Дохід паблішингу (artist.percentage)
    $publishing = ...
    
    // Дохід дистрибуції (artist.percentage_distribution)
    $distribution = ...
    
    return ['publishing' => $publishing, 'distribution' => $distribution];
}

// Для Artist:
public static function getArtistIncome(int $artistId, int $currencyId, int $quarter, int $year): array
{
    // Дохід від своїх треків
    $direct = ...
    
    // Дохід від фітів
    $features = ...
    
    // Дохід лейбу від цього артиста
    $label = ...
    
    return ['direct' => $direct, 'features' => $features, 'label' => $label];
}
```

### Виправлення #2: Розділити розрахунок ii.artist_id vs ii.from_artist_id

```php
// Поточна помилка:
WHERE (ii.artist_id = :artist_id OR ii.from_artist_id = :artist_id)

// Правильно:
// Для прямого доходу артиста:
WHERE ii.artist_id = :artist_id AND ii.from_artist_id IS NULL

// Для доходу лейбу від артиста:
WHERE ii.artist_id = 0 AND ii.from_artist_id = :artist_id
```

---

## 📊 МАТРИЦЯ ПОМИЛОК

| Метод | Призначення | Помилка | Вплив |
|-------|-----------|:-------:|:-----:|
| getArtistDirectIncome() | Artist прямий дохід | Немає розділення від фітів | Неправильно |
| getArtistFeatureIncome() | Artist фічи | Немає | ОК? |
| getLabelIncomeFromArtist() | Дохід лейбу | Не розділює Partner/Artist | Неправильно |
| getTotalIncomeForArtist() | Всього для Artist | Складає ii.artist_id + ii.from_artist_id | Дублюється! |
| **Немає методу для Partner** | Partner розрахунок | Використовується Artist логіка! | Дублюється/Неправильно |

---

## 🔴 ВИСНОВОК

### Основна проблема:
```
ArtistBalanceQueryBuilder розраховує Artist та Partner ОДНАКОВО
Але вони АБСОЛЮТНО РІЗНІ у Track::getCalculation()!

Artist: складна логіка (track_to_percentage для кожного)
Partner: проста логіка (artist.percentage для всього)
```

### Практичні наслідки:
```
❌ Partner отримують неправильні суми
❌ Artist з фічами отримують неправильні суми
❌ Лейбли отримують подвійне добування з invoice_items
```

### Рішення:
```
✅ Створити окремі методи для Partner
✅ Розділити ii.artist_id та ii.from_artist_id логіку
✅ Перевірити всі розрахунки
✅ Написати тести
```

---

**Статус:** 🔴 КРИТИЧНІ ПОМИЛКИ В ОСНОВНІЙ ЛОГІЦІ  
**Ключовий момент:** Система бонусів - це дрібниця. **Справжня проблема - це неправильний розрахунок Artist vs Partner!**


