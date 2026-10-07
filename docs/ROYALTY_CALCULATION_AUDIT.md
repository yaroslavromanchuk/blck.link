# 🔍 АНАЛІЗ ЛОГІКИ РОЗРАХУНКІВ РОЯЛТІ v2.0

**Дата аналізу:** October 7, 2024  
**Статус:** Перевірено після розгортання у production  
**Критичність знайдених проблем:** ВИСОКА

---

## ⚠️ КРИТИЧНІ ПРОБЛЕМИ

### 🔴 ПРОБЛЕМА 1: Неправильний розрахунок бонусів по артистах

**Локація:** `backend/models/Invoice.php`, метод `calculateUser()` (лінія 394-437)

**Проблема:**
```php
// ❌ НЕПРАВИЛЬНО: Для кожного item в циклі розраховуємо суму
foreach ($this->getInvoiceItems()->all() as $item) {
    $usersFromArtist = UserBonus::getUserToArtist($item->artist_id);
    
    if ($usersFromArtist) {
        $sumLabel = $item->getLabelSumFromArtist();  // ← ПРОБЛЕМА!
        // Рахуємо суму N разів для одного артиста!
    }
}
```

**Чому це ошибка:**
- Якщо в інвойсі 100 строк для одного артиста, `getLabelSumFromArtist()` буде викликана 100 разів!
- Результат буде одинаковий кожний раз, але N+1 запити
- Бонуси можуть бути нараховані кілька разів (нема перевірки на дублікати перед циклом)

**Наслідок:**
- 📊 Менеджери отримають кілька бонусів для одного артиста
- 💰 Розчети можуть бути невірними (дублювання бонусів)
- ⏱️ Повільна обробка великих інвойсів

**Рішення:**
```php
// ✅ ПРАВИЛЬНО: Групуємо артистів і рахуємо один раз
$artistSums = [];  // Кеш для сум по артистах

foreach ($this->getInvoiceItems()->all() as $item) {
    if (!isset($artistSums[$item->artist_id])) {
        $artistSums[$item->artist_id] = $item->getLabelSumFromArtist();
    }
}

foreach ($artistSums as $artistId => $sumLabel) {
    $usersFromArtist = UserBonus::getUserToArtist($artistId);
    
    if ($usersFromArtist && $sumLabel > 0) {
        // Обробляємо один раз за артиста
    }
}
```

---

### 🔴 ПРОБЛЕМА 2: Відсутня перевірка на дублювання бонусів

**Локація:** `backend/models/Invoice.php`, метод `calculateUser()` (лінія 363-375)

**Проблема:**
```php
$b = UserBalance::findOne([
    'invoice_id' => $this->invoice_id,
    'currency_id' => $this->currency_id,
    'user_id' => $user->user_id,
    'label_id' => $user->label_id,  // ← Лейбл указан
]);

if ($b) {
    continue;  // Пропускаємо
}

// Але якщо це перший раз, $b = null, і ми добавляємо запис
// Потім якщо функция викличется снова для того же інвойсу - запис буде дублювано!
```

**Чому це ошибка:**
- Якщо `calculateUser()` викличется двічі для одного інвойсу → бонусы будуть двічі
- Нема транзакції для запобігання race-conditions
- Перевірка після першого додавання не працює

**Наслідок:**
- 💰 Менеджери отримають подвійні бонусы
- 📊 Звіти будуть невірними

---

### 🔴 ПРОБЛЕМА 3: Неправильна логіка розрахунку "Прямого доходу" артиста

**Локація:** `backend/services/ArtistBalanceQueryBuilder.php`, метод `getArtistDirectIncome()`

**Проблема:**
```sql
SELECT SUM(ii.amount) as amount
FROM `invoice_items` ii
INNER JOIN `invoice` i ON i.invoice_id = ii.invoice_id
INNER JOIN `track` t ON t.id = ii.track_id  -- ← JOIN на track!
WHERE ...
  AND ii.artist_id = :artist_id
  AND t.artist_id = ii.artist_id  -- ← Прямой доход
```

**Чому це ошибка:**
- Передбачається що `invoice_items` має `track_id`, але якщо трек не знайдено → запис вип'падає
- Якщо `track.artist_id` не дорівнює `ii.artist_id` → дохід не буде розраховано
- Нема обробки case коли трек видалено або не синхронізовано

**Наслідок:**
- 📉 Дохід артиста буде недораховано
- 💰 Артисти отримають менше грошей

---

### 🟡 ПРОБЛЕМА 4: Відсутня логіка розподілу відсотків для фітів

**Локація:** Всі файли

**Проблема:**
- Система розраховує доходи по артистах, але **нема логіки розподілу відсотків** коли у треку кілька артистів
- Якщо трек має: Автор 50%, Фіч1 30%, Фіч2 20% → система не знає як розділити доход
- Таблиця `track_to_percentage` не використовується у розрахунках

**Чому це ошибка:**
- 🎵 Система не вміє працювати з фічами правильно
- 💰 Фіч-артисти можуть не отримати свою частку
- 📊 Звіти не відображають справжній дохід

**Наслідок:**
- Неправильний розподіл доходу між артистами одного треку
- Артисти не отримають справедливу частку

---

### 🟡 ПРОБЛЕМА 5: Публішинг % розраховується неправильно

**Локація:** `backend/models/Artist.php`, поле `percentage`

**Проблема:**
- Поле `artist.percentage` - це публішинг %
- Але він застосовується до **всього** доходу, не лише до авторських прав
- Якщо артист має 80% і його доход 1000 → він отримує 800
- Але якщо у треку є фічі → 80% розраховуються не від правильної суми

**Чому це ошибка:**
- 📊 Публішинг % повинен розраховуватися від **авторської частки**, не від загального доходу
- 💰 Артисти отримають більше або менше залежно від фічів

---

### 🟡 ПРОБЛЕМА 6: Нема обробки "Видавничих прав" (Ownership Types)

**Локація:** Таблиці `ownership`, `ownership_type`, `track_to_percentage` існують, але не використовуються

**Проблема:**
```php
// У таблиці track_to_percentage є:
// - percentage: відсоток
// - ownership_type: тип власності (Авторські права, Видавничі права, і т.д.)
// 
// Але система не використовує ownership_type при розрахунках!
```

**Чому це ошибка:**
- 📋 Видавничі права розраховуються як авторські, це неправильно
- 💰 Видавці можуть не отримати свою частку
- 🎵 Система не розрізняє типи прав

---

## ⚠️ РЕКОМЕНДАЦІЇ ДО ВИПРАВЛЕННЯ

### КРИТИЧНІ (Виконати ОДРАЗУ)

#### 1. Виправити розрахунок бонусів по артистах
**Файл:** `backend/models/Invoice.php`

```php
public function calculateUser(): void
{
    // ... код для лейблів ...

    // Розрахунок по артистах (ВИПРАВЛЕНО)
    $artistSums = [];  // Кеш для сум
    $processedArtists = [];  // Для запобігання дублюванню

    // Спочатку розраховуємо суми по артистах
    foreach ($this->getInvoiceItems()->all() as $item) {
        if ($item->artist_id > 0) {
            if (!isset($artistSums[$item->artist_id])) {
                $artistSums[$item->artist_id] = $item->getLabelSumFromArtist();
            }
        }
    }

    // Потім обробляємо бонусы
    foreach ($artistSums as $artistId => $sumLabel) {
        if ($sumLabel <= 0) continue;
        
        $usersFromArtist = UserBonus::getUserToArtist($artistId);
        
        if ($usersFromArtist) {
            foreach ($usersFromArtist as $user) {
                // Перевіряємо дублікати ПЕРЕД обробкою
                if (!isset($processedArtists[$artistId][$user->user_id])) {
                    $b = UserBalance::findOne([
                        'invoice_id' => $this->invoice_id,
                        'currency_id' => $this->currency_id,
                        'user_id' => $user->user_id,
                        'artist_id' => $artistId,
                    ]);
                    
                    if (!$b) {
                        UserBalance::add([
                            'invoice_id' => $this->invoice_id,
                            'currency_id' => $this->currency_id,
                            'all_sum' => $sumLabel,
                            'percentage' => $user->percentage,
                            'user_id' => $user->user_id,
                            'artist_id' => $artistId,
                            'amount' => round($sumLabel * ($user->percentage / 100), 3),
                        ]);
                    }
                    
                    $processedArtists[$artistId][$user->user_id] = true;
                }
            }
        }
    }
}
```

#### 2. Додати транзакцію та lock'и до calculateUser()
```php
public function calculateUser(): void
{
    $transaction = Yii::$app->db->beginTransaction();
    try {
        // ... вся логіка обробки ...
        $transaction->commit();
    } catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
    }
}
```

#### 3. Виправити INNER JOIN на LEFT JOIN у ArtistBalanceQueryBuilder
```php
// Змінити з:
INNER JOIN `track` t ON t.id = ii.track_id

// На:
LEFT JOIN `track` t ON t.id = ii.track_id
```

---

### ВИСОКОЇ ПРІОРИТЕТУ (У наступному спринті)

#### 4. Реалізувати логіку розподілу відсотків для фітів

**Файл:** Новий сервіс `backend/services/TrackPercentageService.php`

```php
class TrackPercentageService
{
    /**
     * Розподілити дохід треку по артистах з урахуванням відсотків
     */
    public static function distributeTrackIncome(
        int $trackId,
        float $totalIncome,
        int $currencyId
    ): array {
        // Отримати всіх артистів з їх відсотками
        $percentages = Percentage::find()
            ->where(['track_id' => $trackId])
            ->all();
        
        $distribution = [];
        
        foreach ($percentages as $pct) {
            $amount = $totalIncome * ($pct->percentage / 100);
            $distribution[$pct->artist_id] = [
                'amount' => $amount,
                'percentage' => $pct->percentage,
                'ownership_type' => $pct->ownership_type,
            ];
        }
        
        return $distribution;
    }
}
```

#### 5. Реалізувати розрахунок Видавничих прав

**Логіка:**
```php
// Видавничі права розраховуються окремо від авторських
// Якщо:
// - Авторські права: Artist A - 100%
// - Видавничі права: Publisher B - 80%, Artist A - 20%
// 
// То:
// - Artist A отримує: Авторські 100% + Видавничі 20% = сума
// - Publisher B отримує: Видавничі 80% = сума
```

---

### СЕРЕДНЯ ПРІОРИТЕТУ (Оптимізація)

#### 6. Кешувати результати getLabelSumFromArtist()

```php
private $labelSumCache = [];

public function getLabelSumFromArtist(): float
{
    if (!isset($this->labelSumCache[$this->artist_id])) {
        // ... розрахунок ...
        $this->labelSumCache[$this->artist_id] = $sum;
    }
    return $this->labelSumCache[$this->artist_id];
}
```

#### 7. Додати логування усіх бонусів

```php
// Логувати кожне додавання бонусу
\Yii::info([
    'action' => 'user_bonus_added',
    'invoice_id' => $this->invoice_id,
    'user_id' => $user->user_id,
    'artist_id' => $artistId,
    'all_sum' => $sumLabel,
    'percentage' => $user->percentage,
    'amount' => round($sumLabel * ($user->percentage / 100), 3),
], 'royalty');
```

---

## 📊 ТЕСТУВАННЯ

### Тест 1: Перевірити дублювання бонусів

```sql
-- Перевірити що бонусы не дублюються
SELECT 
    user_id, 
    artist_id, 
    label_id,
    COUNT(*) as cnt
FROM user_balance
WHERE invoice_id = 100
GROUP BY user_id, artist_id, label_id
HAVING cnt > 1;

-- Якщо є результати → проблема!
```

### Тест 2: Перевірити правильність розрахунків

```php
// Для кожного инвойса:
$invoice = Invoice::findOne(100);
$invoice->calculateUser();

// Перевірити що:
// sum(user_balance.amount) <= invoice.total
// Для кожного user_id:
// sum(percentage) <= 100%
```

### Тест 3: Перевірити доходи артистів

```sql
-- Перевірити що дохід від фітів + прямий дохід = загальний
SELECT 
    artist_id,
    directIncome + featureIncome as calculated,
    totalIncome as actual
FROM artist_log
WHERE calculated != actual;

-- Якщо є результати → проблема в розрахунках!
```

---

## ✅ КОНТРОЛЬНИЙ СПИСОК

- [ ] **КРИТИЧНО:** Виправити дублювання бонусів в calculateUser()
- [ ] **КРИТИЧНО:** Додати транзакцію до calculateUser()
- [ ] **КРИТИЧНО:** Виправити INNER JOIN на LEFT JOIN
- [ ] **ВИСОКА:** Реалізувати розподіл відсотків для фітів
- [ ] **ВИСОКА:** Реалізувати розрахунок Видавничих прав
- [ ] **СЕРЕДНЯ:** Додати кешування getLabelSumFromArtist()
- [ ] **СЕРЕДНЯ:** Додати логування бонусів
- [ ] Запустити тести перевірки
- [ ] Перевірити звіти для 10+ артистів
- [ ] Розгорнути виправлення у production

---

## 📞 НАСТУПНІ КРОКИ

1. **Негайно (сьогодні):**
   - Перевірити БД на дублювання бонусів
   - Застосувати виправлення 1-3

2. **Цей тиждень:**
   - Реалізувати виправлення 4-5
   - Написати тести

3. **Цей місяць:**
   - Оптимізація 6-7
   - Повна перевірка звітів

---

**Статус:** 🔴 ПОТРЕБУЄ ВИПРАВЛЕННЯ  
**Критичність:** ВИСОКА  
**Рекомендація:** Виправити ОДРАЗУ після перевірки

