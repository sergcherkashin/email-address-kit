# Domains: providers and equivalents

The library models two different relationships between domains. Do not mix them up.

## Equivalent domains — same mailbox

The same mailbox is reachable under multiple provider domains.

```php
Email::parse('anna@ya.ru')->equals('anna@yandex.ru');                 // true
Email::parse('anna@googlemail.com')->equals('anna@gmail.com');       // true

Email::parse('anna@ya.ru')->canonical();       // anna@yandex.ru
Email::parse('anna@yandex.ru')->canonical();   // anna@yandex.ru
```

Provider definition:

```php
return [
    'id' => 'yandex',
    'name' => 'Yandex',
    'domains' => ['yandex.ru', 'ya.ru'],
    'equivalents' => [
        'yandex.ru' => ['ya.ru'],
    ],
];
```

In the compiled index, field `c` (canonical) is written **only** when it differs from the domain itself:

```php
'yandex.ru' => ['s' => 'yandex'],
'ya.ru'     => ['s' => 'yandex', 'c' => 'yandex.ru'],
```

## Provider domains — same service, different mailboxes

Several brand domains, but **different** mailboxes.

```php
Email::parse('anna@mail.ru')->equals('anna@inbox.ru'); // false

Email::parse('anna@mail.ru')
    ->domain()
    ->sameProviderAs('inbox.ru'); // true

$email->service()->id();   // mailru
$email->service()->name(); // Mail.ru
```

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
    // no equivalents
];
```

## Summary

| Relationship | `equals` / `canonical` | `sameProviderAs` | Example |
|---|---|---|---|
| Equivalents | yes, same mailbox | yes (same service) | `ya.ru` ↔ `yandex.ru` |
| Provider only | no | yes | `mail.ru` ↔ `inbox.ru` |
| Different services | no | no | `gmail.com` ↔ `mail.ru` |

## Domain API

```php
$d = Email::parse('anna@ya.ru')->domain();

$d->isKnown();          // true
$d->canonical();        // yandex.ru
$d->equivalents();
$d->providerDomains();
$d->sameProviderAs('yandex.ru'); // true
```

Unknown domain:

```php
$d = Email::parse('anna@my-company.example')->domain();
$d->isKnown();       // false
$d->canonical();     // my-company.example
$d->service();       // null
```

Data source: `resources/domains/providers/*.php` → `php bin/compile-domains` → `resources/compiled/domains.php`.

Next: [Comparing addresses](07-comparison.md)
