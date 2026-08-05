# Email Address Kit

[Русский](README.ru.md) · [English](README.md)

PHP-библиотека для разбора, валидации, нормализации, сравнения и исправления опечаток в email-адресах.

Требования: **PHP 7.4+**. Без привязки к фреймворкам.

**Полная документация:** [docs/ru](docs/ru/README.md) · [docs/en](docs/en/README.md)


## Установка

```bash
composer require email-address-kit/email-address-kit
```


## Быстрый старт



### Разбор email на local-part и domain

`Email::parse()` разбирает строку на части. Некорректный ввод не бросает исключение — сначала объект, потом проверка через `isValid()`.

```php
use EmailAddressKit\Email;

$email = Email::parse('Alex.Petrov+News@Ya.RU');

$email->original();              // Alex.Petrov+News@Ya.RU
$email->address()->value();      // Alex.Petrov+News
$email->domain()->value();       // Ya.RU
(string) $email;                 // alex.petrov+news@ya.ru
```

---

### Три способа получить строку email — для разных задач

| Метод | Что делает | Когда использовать |
|---|---|---|
| `original()` | как ввёл пользователь, без изменений | логи «что пришло», отладка |
| `normalized()` / `(string)` | только lowercase, без догадок | отображение, шаблоны, безопасная печать |
| `canonical()` | ключ mailbox: те же правила, что `equals()` | UNIQUE в БД, дедуп, поиск «тот же ящик» |

```php
$email = Email::parse('Kate+Shop@Ya.RU');

$email->original();     // Kate+Shop@Ya.RU
$email->normalized();   // kate+shop@ya.ru
(string) $email;        // kate+shop@ya.ru  (= normalized)
$email->canonical();    // kate+shop@yandex.ru
```

Важно:

- `normalized()` **не** сводит equivalents (`ya.ru` остаётся `ya.ru`) и **не** убирает `+tag` / точки.
- `canonical()` сводит equivalents (`ya.ru` → `yandex.ru`) и учитывает флаги сравнения, как `equals()`:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

Email::parse('Ka.Te+News@Gmail.com')->canonical();
// ka.te+news@gmail.com

Email::parse('Ka.Te+News@Gmail.com')->canonical([
    ComparisonOptions::ignoreGmailDots(),
    ComparisonOptions::ignorePlusTag(),
]);
// kate@gmail.com
```

Два адреса — один mailbox ⟺ их `canonical()` совпадают (при тех же флагах).

---

### Название сервиса по домену

Если домен есть в справочнике провайдеров, можно получить сервис: стабильный `id` и человекочитаемое `name`.

```php
$email = Email::parse('kate@ya.ru');

$email->domain()->isKnown(); // true

$service = $email->service();
// то же: $email->domain()->service()

$service->id();   // yandex
$service->name(); // Yandex
```

Примеры:

```php
Email::parse('a@gmail.com')->service()->name();       // Gmail
Email::parse('a@googlemail.com')->service()->name();  // Gmail (тот же сервис)
Email::parse('a@mail.ru')->service()->name();         // Mail.ru
Email::parse('a@inbox.ru')->service()->name();        // Mail.ru
```

Неизвестный домен — сервиса нет:

```php
$email = Email::parse('anna@my-company.example');

$email->domain()->isKnown(); // false
$email->service();           // null
```

Домены одного сервиса, но **разные** ящики, можно отличить через `sameProviderAs` (это не `equals`):

```php
Email::parse('a@mail.ru')->domain()->sameProviderAs('inbox.ru'); // true
Email::parse('a@mail.ru')->equals('a@inbox.ru');                 // false
```

Список всех известных сервисов — map `id => name`:

```php
use EmailAddressKit\EmailFactory;

$services = EmailFactory::default()->registry()->services();
// [
//   'gmail' => 'Gmail',
//   'mailru' => 'Mail.ru',
//   'yandex' => 'Yandex',
//   ...
// ]

$services['gmail']; // Gmail
```

Также доступен список доменов: `registry()->domains()`.

---

### Поддержка кириллицы (IDN / EAI)

Библиотека умеет работать с unicode в **домене** и в **local-part**.

#### Домен (IDN → Punycode)

Нужно расширение PHP **`intl`**. Unicode-домен и его ASCII-форма считаются одним и тем же при `equals()`.

```php
$email = Email::parse('info@почта.рф');

$email->isValid();           // true (с intl)
$email->normalized();        // info@почта.рф
$email->domain()->value();   // почта.рф
$email->domain()->ascii();   // xn--80a1acny.xn--p1ai

Email::parse('info@почта.рф')
    ->equals('info@xn--80a1acny.xn--p1ai'); // true
```

Без `intl` не-ASCII **домен** → `isValid() === false`, код `INVALID_DOMAIN`.

```bash
php -m | findstr intl
# или: php -m | grep intl
```

#### Local-part (EAI)

UTF-8 буквы/цифры в имени ящика допустимы и **не** переводятся в Punycode. Работает и **без** `intl`.

```php
Email::parse('иван@gmail.com')->isValid();            // true
Email::parse('иван.петров@gmail.com')->isValid();     // true
Email::parse('инфо@компания.онлайн')->isValid();      // true (домен — при наличии intl)
```

Сравнение/нормализация local-part — с учётом unicode lowercase, где это возможно.

---

### Синтаксическая валидация

Проверяется формат адреса: наличие `@`, пустые части, недопустимые символы, длина, структура домена.

```php
$email = Email::parse('hello@world.com');
$email->isValid(); // true

$bad = Email::parse('not-an-email');
$bad->isValid(); // false

foreach ($bad->validation()->errors() as $error) {
    $error->code();     // например INVALID_FORMAT
    $error->message();
}
```

---

### Опциональная проверка DNS (MX/A)

DNS выключен по умолчанию. При необходимости можно проверить, что у домена есть MX или A-запись.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('nina.k@example.com');

$email->isValid(); // true; только синтаксис

$email->isValid(ValidationOptions::checkDns()); // синтаксис + MX или A у домена

$result = $email->validation(ValidationOptions::checkDns()); // нет DNS-записей → код DNS_CHECK_FAILED
```

DNS выполняется только после успешной синтаксической проверки. Временный сбой DNS может дать ложноотрицательный результат. Это не проверка «ящик существует на сервере».

---

### Сравнение адресов (`equals`)

Сравнивает, один ли это mailbox: без учёта регистра и с учётом equivalent-доменов (`ya.ru` ↔ `yandex.ru`, `googlemail.com` ↔ `gmail.com`).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru');     // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru');    // false (один сервис, разные ящики)
Email::parse('kate+news@mail.ru')->equals('kate@mail.ru'); // false (+tag значим)

// Gmail equivalents — один mailbox:
Email::parse('kate@googlemail.com')->equals('kate@gmail.com'); // true
```

По умолчанию точки в local-part значимы даже у Gmail. Чтобы считать `ka.te@gmail.com` и `kate@gmail.com` одним ящиком:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

Email::parse('ka.te@gmail.com')->equals('kate@gmail.com', ComparisonOptions::ignoreGmailDots()); // true
Email::parse('ka.te@googlemail.com')->equals('kate@gmail.com', ComparisonOptions::ignoreGmailDots()); // true
Email::parse('ka.te@mail.ru')->equals('kate@mail.ru', ComparisonOptions::ignoreGmailDots()); // false (не Gmail)
```

Точки Gmail и `+tag` можно комбинировать массивом флагов:

```php
Email::parse('ka.te+news@gmail.com')->equals('kate@gmail.com', [
    ComparisonOptions::ignoreGmailDots(),
    ComparisonOptions::ignorePlusTag(),
]); // true
```

Чтобы игнорировать `+tag` у всех провайдеров:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

Email::parse('kate+news@mail.ru')->equals('kate@mail.ru', ComparisonOptions::ignorePlusTag()); // true
```

---

### Поиск совпадений в массиве (`filterEquals`)

Ищет в списке (например из БД) все адреса того же mailbox. Ключи и исходные строки сохраняются.

```php
$needle = Email::parse('kate@ya.ru');

$needle->filterEquals([
    'bob@yandex.ru',
    'KATE@yandex.ru',
    'kate@yandex.ru',
    'kate@mail.ru',
]);
// [1 => 'KATE@yandex.ru', 2 => 'kate@yandex.ru']

$needle->filterEquals([
    10 => 'bob@yandex.ru',
    11 => 'KATE@yandex.ru',
    36 => 'kate@yandex.ru',
    42 => 'kate@mail.ru',
]);
// [11 => 'KATE@yandex.ru', 36 => 'kate@yandex.ru']
```

Подходит для коротких списков в памяти. Для больших выборок лучше хранить и искать по `canonical()`.

---

### Определение опечаток в домене

Детектор сравнивает домен с известными провайдерами и предлагает похожие варианты (например `gmial.com` → `gmail.com`).

```php
$email = Email::parse('igor@gmial.com');

$email->hasSuggestions(); // true

foreach ($email->suggestions() as $suggestion) {
    $suggestion->email()->normalized(); // igor@gmail.com
    $suggestion->score();               // 0.0 … 1.0
    $suggestion->reason();              // domain_typo | domain_equivalent
}
```

`domain_typo` — похоже на опечатку.  
`domain_equivalent` — информативное предложение свернуть к canonical (`googlemail.com` → `gmail.com`); в автоисправление как typo не применяется.

---

### Автоисправление опечаток при высокой уверенности

`correct()` применяет только `domain_typo` и только если score не ниже порога (по умолчанию `0.95`). Иначе адрес остаётся без изменений.

```php
Email::parse('igor@gmial.com')->correct()->normalized();
// igor@gmail.com

Email::parse('igor@gmial.com')->correct(0.99); // свой порог
```

---

### Определение disposable / временных доменов

Проверка отдельная от `isValid()`: временный адрес часто синтаксически корректен.

```php
Email::parse('demo@mailinator.com')->isValid();      // true
Email::parse('demo@mailinator.com')->isDisposable(); // true
Email::parse('demo@gmail.com')->isDisposable();      // false
```

Список доменов читается из скомпилированного hash-map (O(1)). Опционально учитываются родительские домены: `foo.mailinator.com` тоже считается disposable. Список обновляется через `composer update-disposable` (или офлайн `--local-only`).


## Разработка

```bash
composer test               # PHPUnit
composer psalm              # статический анализ
composer rector             # dry-run рефакторинга
composer compile-domains    # индекс известных доменов
composer update-disposable  # индекс disposable-доменов
```

Конфиги: `phpunit.xml`, `psalm.xml`, `rector.php`.

## Лицензия

MIT
