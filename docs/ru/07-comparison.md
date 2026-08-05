# Сравнение адресов

## equals

```php
$email->equals('Other@Domain.com');
$email->equals($otherEmailObject);
$email->equals($other, ComparisonOptions::ignorePlusTag());
$email->equals($other, ComparisonOptions::ignoreGmailDots());
```

Сравнение case-insensitive и учитывает **equivalents**.  
Невалидный тип аргумента → `false` (не исключение).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru'); // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru'); // false (provider, не equivalent)
Email::parse('kate@googlemail.com')->equals('kate@gmail.com'); // true
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

По умолчанию точки значимы везде, включая Gmail:

```php
Email::parse('ka.te@gmail.com')->equals('kate@gmail.com'); // false
```

Игнорировать точки только у Gmail / `googlemail.com`:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$opt = ComparisonOptions::ignoreGmailDots();

Email::parse('ka.te@gmail.com')->equals('kate@gmail.com', $opt);      // true
Email::parse('ka.te@googlemail.com')->equals('kate@gmail.com', $opt); // true
Email::parse('ka.te@mail.ru')->equals('kate@mail.ru', $opt);          // false
```

Комбинация с `+tag` — массивом флагов:

```php
Email::parse('ka.te+news@gmail.com')->equals('kate@gmail.com', [
    ComparisonOptions::ignoreGmailDots(),
    ComparisonOptions::ignorePlusTag(),
]); // true
```

## canonical

Стабильный ключ mailbox — те же правила, что `equals()`:

```php
Email::parse('Kate+Shop@Ya.RU')->canonical();
// kate+shop@yandex.ru

Email::parse('Kate+Shop@Ya.RU')->canonical(ComparisonOptions::ignorePlusTag());
// kate@yandex.ru

Email::parse('Ka.Te@Gmail.com')->canonical(ComparisonOptions::ignoreGmailDots());
// kate@gmail.com
```

Два адреса равны ⟺ их `canonical()` совпадают (при тех же options).

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

## Сводка правил

| Правило | Как | Пример |
|---|---|---|
| `+tag` значим | default | `a+x@…` ≠ `a@…` |
| `+tag` игнор | `ComparisonOptions::ignorePlusTag()` | `a+x@…` = `a@…` |
| точки игнор | `ComparisonOptions::ignoreGmailDots()` (только Gmail) | `a.b@gmail.com` = `ab@gmail.com` |
| equivalents | всегда | `…@googlemail.com` = `…@gmail.com` |

`Email::normalized()` **никогда** не зависит от options сравнения.

Далее: [Опечатки и автоисправление](08-typo-correction.md)
