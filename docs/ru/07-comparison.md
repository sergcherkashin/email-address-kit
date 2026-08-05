# Сравнение адресов

## equals

```php
$email->equals('Other@Domain.com');
$email->equals($otherEmailObject);
$email->equals($other, ComparisonOptions::ignorePlusTag());
```

Сравнение case-insensitive и учитывает **equivalents**.  
Невалидный тип аргумента → `false` (не исключение).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru'); // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru'); // false (provider, не equivalent)
```

## Plus-tag

По умолчанию `+tag` **значим** у всех провайдеров:

```php
Email::parse('kate+news@mail.ru')->equals('kate@mail.ru');   // false
Email::parse('kate+news@gmail.com')->equals('kate@gmail.com'); // false
```

Игнорировать у всех:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$opt = ComparisonOptions::ignorePlusTag();

Email::parse('kate+news@mail.ru')->equals('kate@mail.ru', $opt);   // true
Email::parse('kate+news@gmail.com')->equals('kate@gmail.com', $opt); // true
```

## Точки в local-part

В **default**-стратегии точки значимы везде, включая Gmail:

```php
Email::parse('ka.te@gmail.com')->equals('kate@gmail.com'); // false
```

Правила Gmail (игнор точек) — только в `GmailComparisonStrategy` (см. ниже).

## canonical

Стабильный ключ mailbox — те же правила, что `equals()`:

```php
Email::parse('Kate+Shop@Ya.RU')->canonical();
// kate+shop@yandex.ru

Email::parse('Kate+Shop@Ya.RU')->canonical(ComparisonOptions::ignorePlusTag());
// kate@yandex.ru
```

Два адреса равны ⟺ их `canonical()` совпадают (при тех же options и той же стратегии).

Практическое применение в БД:

```php
$row['email_canonical'] = Email::parse($row['email'])->canonical();
// UNIQUE(email_canonical)
```

## filterEquals

Найти в массиве все адреса того же mailbox. **Ключи и исходные строки сохраняются.**

```php
$needle = Email::parse('kate@ya.ru');

$needle->filterEquals([
    'bob@yandex.ru',
    'KATE@yandex.ru',
    'kate@yandex.ru',
    'kate@mail.ru',
]);
// [
//   1 => 'KATE@yandex.ru',
//   2 => 'kate@yandex.ru',
// ]

$needle->filterEquals([
    10 => 'bob@yandex.ru',
    11 => 'KATE@yandex.ru',
    36 => 'kate@yandex.ru',
    42 => 'kate@mail.ru',
]);
// [
//   11 => 'KATE@yandex.ru',
//   36 => 'kate@yandex.ru',
// ]
```

Подходит для коротких списков в памяти.  
Для больших выборок и UNIQUE удобнее хранить/искать по `canonical()`.

## Стратегии сравнения

### DefaultComparisonStrategy (по умолчанию)

- lowercase local-part;
- canonical domain из equivalents;
- `+tag` значим;
- точки значимы.

### GmailComparisonStrategy

Дополнительно для Gmail / `googlemail.com`: точки в local-part игнорируются.

| Правило | Где | Пример |
|---|---|---|
| `+tag` значим | все (default) | `a+x@…` ≠ `a@…` |
| `+tag` игнор | флаг options | `a+x@…` = `a@…` |
| точки игнор | только Gmail-стратегия | `a.b@gmail.com` = `ab@gmail.com` |
| equivalents | всегда в стратегии | `…@googlemail.com` = `…@gmail.com` |

Подключение:

```php
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

Email::parse('ka.te@gmail.com')->equals('kate@gmail.com'); // true
Email::parse('ka.te@mail.ru')->equals('kate@mail.ru');     // false

Email::parse('ka.te@gmail.com')->canonical(); // kate@gmail.com
```

`Email::normalized()` **никогда** не зависит от стратегии сравнения.

Далее: [Опечатки и автоисправление](08-typo-correction.md)
