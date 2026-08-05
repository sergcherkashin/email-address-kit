# Core concepts

## Four address states

```text
raw        →  what the user typed
parsed     →  split into Address + Domain
normalized →  safe lowercase, no assumptions
corrected  →  speculative typo fix
```

Example:

| State | Value |
|---|---|
| raw / `original()` | `Alex.Petrov+News@Ya.RU` |
| parsed | Address=`Alex.Petrov+News`, Domain=`Ya.RU` |
| `normalized()` | `alex.petrov+news@ya.ru` |
| `correct()` (domain typo) | may rewrite domain only at high confidence |

**Normalization never:**

- fixes typos;
- strips `+tag`;
- strips dots in the local-part;
- rewrites `ya.ru` → `yandex.ru`.

## Three string views of Email

| Method | Purpose | Example for `Alex@Ya.RU` |
|---|---|---|
| `original()` | raw input | `Alex@Ya.RU` |
| `normalized()` / `(string)` | logs, templates, display | `alex@ya.ru` |
| `canonical()` | mailbox key for DB / equals | `alex@yandex.ru` |

```php
$email = Email::parse('Alex@Ya.RU');

$email->original();    // Alex@Ya.RU
$email->normalized();  // alex@ya.ru
(string) $email;       // alex@ya.ru
$email->canonical();   // alex@yandex.ru
```

`__toString()` on `Email`, `Address`, and `Domain` always equals that object’s `normalized()`.

## Why canonical is separate

`equals('a@ya.ru', 'a@yandex.ru') === true`, but `normalized()` keeps the domain as entered (lowercased).

For UNIQUE indexes and DB lookup you need a stable key → `canonical()`.

It uses the **same comparison strategy** as `equals()`:

- default: lowercase local-part + canonical domain;
- with `ComparisonOptions::ignorePlusTag()` — without `+tag`;
- with `GmailComparisonStrategy` — for Gmail, dots in the local-part are also removed.

## Address and Domain

```php
$email->address()->value();
$email->address()->normalized();

$email->domain()->value();
$email->domain()->normalized();
$email->domain()->ascii();
$email->domain()->canonical();    // domain only (ya.ru → yandex.ru)
$email->domain()->isKnown();
$email->domain()->service();
$email->domain()->equivalents();
$email->domain()->providerDomains();
$email->domain()->sameProviderAs($other);
```

Note: `Email::canonical()` is the **full** `local@domain` key.  
`Domain::canonical()` is the **domain part only**.

## Debug (`var_dump`)

`__debugInfo()` exposes identity fields, including computed `isValid` and `isDisposable`.

Nuance: the disposable map loads lazily on the **first** `isDisposable()` call.  
`var_dump($email)` triggers that inside debug and may warm a large map — fine for debugging; production code that never calls `isDisposable()` does not load the file.

Next: [Parsing and validation](05-parsing-validation.md)
