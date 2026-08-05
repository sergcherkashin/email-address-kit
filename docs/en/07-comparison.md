# Comparing addresses

## equals

```php
$email->equals('Other@Domain.com');
$email->equals($otherEmailObject);
$email->equals($other, ComparisonOptions::ignorePlusTag());
```

Comparison is case-insensitive and respects **equivalents**.  
Invalid argument types yield `false` (no exception).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru'); // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru'); // false (provider, not equivalent)
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

With the **default** strategy, dots are significant everywhere, including Gmail:

```php
Email::parse('ka.te@gmail.com')->equals('kate@gmail.com'); // false
```

Gmail-style “ignore dots” lives only in `GmailComparisonStrategy` (below).

## canonical

Stable mailbox key — same rules as `equals()`:

```php
Email::parse('Kate+Shop@Ya.RU')->canonical();
// kate+shop@yandex.ru

Email::parse('Kate+Shop@Ya.RU')->canonical(ComparisonOptions::ignorePlusTag());
// kate@yandex.ru
```

Two addresses are equal ⟺ their `canonical()` values match (same options, same strategy).

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

## Comparison strategies

### DefaultComparisonStrategy (default)

- lowercase local-part;
- canonical domain from equivalents;
- `+tag` significant;
- dots significant.

### GmailComparisonStrategy

Additionally for Gmail / `googlemail.com`: dots in the local-part are ignored.

| Rule | Where | Example |
|---|---|---|
| `+tag` significant | all (default) | `a+x@…` ≠ `a@…` |
| `+tag` ignored | options flag | `a+x@…` = `a@…` |
| dots ignored | Gmail strategy only | `a.b@gmail.com` = `ab@gmail.com` |
| equivalents | always in strategy | `…@googlemail.com` = `…@gmail.com` |

Enable:

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

`Email::normalized()` **never** depends on the comparison strategy.

Next: [Typos and auto-correct](08-typo-correction.md)
