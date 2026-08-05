# Email Address Kit

[Русский](README.ru.md) · [English](README.md)

PHP-библиотека для разбора, валидации, нормализации, сравнения и исправления опечаток в email-адресах.

Требования: **PHP 7.4+**. Без привязки к фреймворкам.

**Полная документация:** [docs/ru](docs/ru/README.md) · [docs/en](docs/en/README.md)

Для IDN-доменов (`почта.рф`, `компания.онлайн` и т.п.) нужно расширение **`intl`** (`idn_to_ascii`).  
Без него unicode-домены будут `isValid() === false` с кодом `INVALID_DOMAIN`.  
UTF-8 в local-part (EAI: `иван@gmail.com`) работает и без `intl`.

Проверка:

```bash
php -m | findstr intl
# или
php -m | grep intl
```

## Установка

```bash
composer require email-address-kit/email-address-kit
```

Локальная разработка:

```bash
composer install
php bin/compile-domains
```

## Быстрый старт

```php
use EmailAddressKit\Email;

$email = Email::parse('User.Name@Gmial.com');

$email->address()->value();       // User.Name
$email->domain()->value();        // Gmial.com
$email->isValid();                // true
$email->normalized();             // user.name@gmial.com
$email->hasSuggestions();         // true
$email->correct()->normalized();  // user.name@gmail.com
```

## Основные возможности

| Возможность | Описание |
|---|---|
| Парсинг | `Email::parse()` — некорректный ввод не бросает исключение |
| Валидация | синтаксическая, типизированные ошибки |
| Нормализация | только lowercase; без правок опечаток, `+tag` и точек |
| Сравнение | case-insensitive + canonical; `+tag` значим по умолчанию |
| Typo detection | опечатки в домене среди известных доменов |
| Auto-correct | только при высоком confidence (`domain_typo`) |
| Disposable | `isDisposable()` — временные домены (отдельно от валидации) |

## Provider vs Equivalents

Два разных отношения между доменами:

**Equivalent domains** — один и тот же mailbox:

```php
Email::parse('one@ya.ru')->equals('one@yandex.ru');           // true
Email::parse('one@googlemail.com')->equals('one@gmail.com'); // true
```

Поиск совпадений среди списка (например из БД) — те же правила, что у `equals()`, ключи и исходные строки сохраняются:

```php
$email = Email::parse('kate@ya.ru');

$email->filterEquals([
    'bob@yandex.ru',
    'KATE@yandex.ru',
    'kate@yandex.ru',
    'kate@mail.ru',
]);
// [1 => 'KATE@yandex.ru', 2 => 'kate@yandex.ru']

$email->filterEquals([
    10 => 'bob@yandex.ru',
    11 => 'KATE@yandex.ru',
    36 => 'kate@yandex.ru',
    42 => 'kate@mail.ru',
]);
// [11 => 'KATE@yandex.ru', 36 => 'kate@yandex.ru']
```

Ключ уникальности для БД / индексов (те же правила, что `equals()`):

```php
Email::parse('Kate@Ya.RU')->canonical();     // kate@yandex.ru
Email::parse('kate@yandex.ru')->canonical(); // kate@yandex.ru

// normalized() домен не сводит:
Email::parse('Kate@Ya.RU')->normalized();    // kate@ya.ru
```

**Provider domains** — один сервис, разные ящики:

```php
Email::parse('one@mail.ru')->equals('one@inbox.ru'); // false

Email::parse('one@mail.ru')
    ->domain()
    ->sameProviderAs('inbox.ru'); // true
```

Подробности: [docs/ru](docs/ru/README.md).

## API

```php
$email = Email::parse($input);

$email->original();
$email->normalized();        // = (string) $email
$email->canonical();         // ключ mailbox (equals / БД)
$email->address();           // Address
$email->domain();            // Domain
$email->isValid();
$email->validation();        // ValidationResult
$email->isValid(\EmailAddressKit\Validation\ValidationOptions::checkDns());
$email->isDisposable();      // временный/одноразовый домен
$email->service();           // ?EmailService
$email->hasSuggestions();
$email->suggestions();       // EmailSuggestion[]
$email->correct(0.95);       // Email
$email->equals($other);      // Email|string
$email->filterEquals($list); // совпадения из массива (ключи сохраняются)
```

`(string) $email` / echo / шаблоны — всегда `normalized()`: lowercase без правок опечаток и без свода equivalents.  
Для сырого ввода — `original()`, для ключа БД — `canonical()`.

### Disposable (временные) домены

Проверка **отдельная** от `isValid()`: адрес может быть синтаксически валидным и при этом disposable.

```php
Email::parse('user@mailinator.com')->isValid();       // true
Email::parse('user@mailinator.com')->isDisposable();  // true
Email::parse('user@gmail.com')->isDisposable();       // false
```

Runtime читает скомпилированный hash-map `resources/compiled/disposable.php` (O(1), плюс опциональный match родительских доменов: `foo.mailinator.com`).

Обновление списка:

```bash
composer update-disposable
# или офлайн из seed:
php bin/update-disposable --local-only
```

Источники настраиваются в `resources/disposable/sources.php` — primary (по умолчанию groundcat) и fallback (локальный `seed.txt`).  
Allowlist реальных провайдеров: `resources/disposable/allowlist.txt`.

Если upstream-репозиторий забросят — меняете URL/тип источника в конфиге, код checker'а трогать не нужно.

Кастомный checker:

```php
use EmailAddressKit\Disposable\DisposableDomainChecker;
use EmailAddressKit\EmailFactory;

$factory = EmailFactory::fromRegistry(
    EmailFactory::default()->registry(),
    null,
    null,
    DisposableDomainChecker::default()
);
```

### DNS / MX

По умолчанию DNS **не** проверяется.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('user@example.com');

$email->isValid(); // только синтаксис

$email->isValid(ValidationOptions::checkDns());
// синтаксис + наличие MX или A у домена

$result = $email->validation(ValidationOptions::checkDns());
// при отсутствии DNS-записей: код DNS_CHECK_FAILED
```

DNS проверяется только после успешной синтаксической валидации.  
Временные сбои DNS могут дать ложноотрицательный результат.

### IDN / Punycode / EAI

- **Домен:** IDN → Punycode (`intl` / `idn_to_ascii`). Unicode и ASCII-формы одного домена равны при `equals()`.
- **Local-part:** EAI — UTF-8 буквы/цифры допустимы, **без** Punycode.

```php
Email::parse('иван@почта.рф')->isValid();                      // true
Email::parse('иван@xn--80a1acny.xn--p1ai')->isValid();         // true
Email::parse('инфо@компания.онлайн')->isValid();                 // true
Email::parse('contact@почта.рф')->isValid();                   // true
Email::parse('иван.иванов@gmail.com')->isValid();              // true

$email = Email::parse('иван@почта.рф');
$email->normalized();          // иван@почта.рф
$email->domain()->ascii();     // xn--80a1acny.xn--p1ai
$email->equals('иван@xn--80a1acny.xn--p1ai'); // true
```

Без `intl` не-ASCII **домены** получают `INVALID_DOMAIN` — это ожидаемое поведение, не баг.  
Включите расширение в `php.ini` (OpenServer: настройки PHP → `intl`).  
UTF-8 в local-part работает и без `intl`.

### Domain

```php
$domain = $email->domain();

$domain->value();
$domain->normalized();
$domain->ascii();           // Punycode / ASCII
$domain->canonical();
$domain->isKnown();
$domain->service();
$domain->equivalents();
$domain->providerDomains();
$domain->sameProviderAs($other);
```

### Suggestions

```php
foreach ($email->suggestions() as $suggestion) {
    $suggestion->email()->normalized();
    $suggestion->score();   // 0.0 … 1.0
    $suggestion->reason();  // domain_typo | domain_equivalent
}
```

`correct()` применяет только `domain_typo` при score ≥ порога.  
Equivalent rewrite (`googlemail.com` → `gmail.com`) используется в `equals()` / `canonical()`, но не в auto-correct.

## Стратегии сравнения

### DefaultComparisonStrategy

Используется по умолчанию:

- lowercase;
- canonical domain из equivalents;
- **`+tag` значим** у всех провайдеров;
- точки в local-part **сохраняются** (значимы).

```php
Email::parse('serg+news@mail.ru')->equals('serg@mail.ru'); // false
Email::parse('ser.g@gmail.com')->equals('serg@gmail.com');   // false
```

Чтобы игнорировать `+tag` у всех:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$options = ComparisonOptions::ignorePlusTag();

Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options); // true
Email::parse('serg+news@gmail.com')->equals('serg@gmail.com', $options); // true
```

### GmailComparisonStrategy

Дополнительно к default: для Gmail / `googlemail.com` точки в local-part игнорируются.

| Правило | Где | Пример |
|---|---|---|
| `+tag` значим | все провайдеры (default) | `serg+news@…` ≠ `serg@…` |
| `+tag` игнорировать | флаг `ComparisonOptions::ignorePlusTag()` | `serg+news@…` = `serg@…` |
| точки игнорируются | только Gmail | `ser.g@gmail.com` = `serg@gmail.com` |
| equivalents | Gmail | `serg@googlemail.com` = `serg@gmail.com` |

```php
use EmailAddressKit\Comparison\ComparisonOptions;
use EmailAddressKit\Comparison\GmailComparisonStrategy;
use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;

$factory = EmailFactory::fromRegistry(
    DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default()),
    new GmailComparisonStrategy()
);
EmailFactory::setDefault($factory);

Email::parse('ser.g@gmail.com')->equals('serg@gmail.com');       // true
Email::parse('ser.g+news@gmail.com')->equals('serg@gmail.com'); // false
Email::parse('ser.g@mail.ru')->equals('serg@mail.ru');           // false

Email::parse('ser.g+news@gmail.com')->equals(
    'serg@gmail.com',
    ComparisonOptions::ignorePlusTag()
); // true

// Нормализация не зависит от стратегии сравнения:
Email::parse('Ser.G+Tag@Gmail.Com')->normalized(); // ser.g+tag@gmail.com
```

`Email::normalized()` **никогда** не удаляет точки и `+tag` — это только правила `equals()`.

## Справочник доменов

Source of truth:

```text
resources/domains/providers/*.php
```

Пример без equivalents:

```php
return [
    'id' => 'mailru',
    'name' => 'Mail.ru',
    'domains' => [
        'mail.ru',
        'inbox.ru',
        'list.ru',
        'bk.ru',
    ],
];
```

С equivalents:

```php
return [
    'id' => 'gmail',
    'name' => 'Gmail',
    'domains' => [
        'gmail.com',
        'googlemail.com',
    ],
    'equivalents' => [
        'gmail.com' => ['googlemail.com'],
    ],
];
```

Сборка runtime-индекса:

```bash
php bin/compile-domains
```

В `resources/compiled/domains.php` поле `c` (canonical) пишется **только** если оно отличается от домена:

```php
'mail.ru' => ['s' => 'mailru'],
'ya.ru'   => ['s' => 'yandex', 'c' => 'yandex.ru'],
```

## Кастомный реестр

```php
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\EmailFactory;

$registry = new DomainRegistry();
$registry->registerProvider('mailru', 'Mail.ru', [
    'mail.ru',
    'inbox.ru',
]);
$registry->register(
    'gmail',
    'Gmail',
    ['gmail.com', 'googlemail.com'],
    ['gmail.com' => ['googlemail.com']]
);

$factory = EmailFactory::fromRegistry($registry);
$email = $factory->parse('user@gmial.com');
```

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
