# Comparing addresses

## equals

```php
$email->equals('Other@Domain.com');
$email->equals($otherEmailObject);
$email->equals($other, ComparisonOptions::ignorePlusTag());
$email->equals($other, ComparisonOptions::ignoreGmailDots());
```

Comparison is case-insensitive and respects **equivalents**.  
Invalid argument types yield `false` (no exception).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru'); // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru'); // false (provider, not equivalent)
Email::parse('kate@googlemail.com')->equals('kate@gmail.com'); // true
```

## Plus-tag

By default `+tag` is **significant** for every provider:

```php
Email::parse('kate+news@mail.ru')->equals('kate@mail.ru');   // false
Email::parse('kate+news@gmail.com')->equals('kate@gmail.com'); // false
```

Ignore for all providers:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$opt = ComparisonOptions::ignorePlusTag();

Email::parse('kate+news@mail.ru')->equals('kate@mail.ru', $opt);   // true
Email::parse('kate+news@gmail.com')->equals('kate@gmail.com', $opt); // true
```

## Dots in the local-part

By default dots are significant everywhere, including Gmail:

```php
Email::parse('ka.te@gmail.com')->equals('kate@gmail.com'); // false
```

Ignore dots for Gmail / `googlemail.com` only:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$opt = ComparisonOptions::ignoreGmailDots();

Email::parse('ka.te@gmail.com')->equals('kate@gmail.com', $opt);      // true
Email::parse('ka.te@googlemail.com')->equals('kate@gmail.com', $opt); // true
Email::parse('ka.te@mail.ru')->equals('kate@mail.ru', $opt);          // false
```

Combine with `+tag` as an array of flags:

```php
Email::parse('ka.te+news@gmail.com')->equals('kate@gmail.com', [
    ComparisonOptions::ignoreGmailDots(),
    ComparisonOptions::ignorePlusTag(),
]); // true
```

## canonical

Stable mailbox key — same rules as `equals()`:

```php
Email::parse('Kate+Shop@Ya.RU')->canonical();
// kate+shop@yandex.ru

Email::parse('Kate+Shop@Ya.RU')->canonical(ComparisonOptions::ignorePlusTag());
// kate@yandex.ru

Email::parse('Ka.Te@Gmail.com')->canonical(ComparisonOptions::ignoreGmailDots());
// kate@gmail.com
```

Two addresses are equal ⟺ their `canonical()` values match (same options).

Database usage:

```php
$row['email_canonical'] = Email::parse($row['email'])->canonical();
// UNIQUE(email_canonical)
```

## filterEquals

Return entries from an array that share this mailbox. **Keys and original values are preserved.**

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

Good for short in-memory lists.  
For large datasets and UNIQUE constraints, store/query by `canonical()`.

## Rules summary

| Rule | How | Example |
|---|---|---|
| `+tag` significant | default | `a+x@…` ≠ `a@…` |
| `+tag` ignored | `ComparisonOptions::ignorePlusTag()` | `a+x@…` = `a@…` |
| dots ignored | `ComparisonOptions::ignoreGmailDots()` (Gmail only) | `a.b@gmail.com` = `ab@gmail.com` |
| equivalents | always | `…@googlemail.com` = `…@gmail.com` |

`Email::normalized()` **never** depends on comparison options.

Next: [Typos and auto-correct](08-typo-correction.md)
