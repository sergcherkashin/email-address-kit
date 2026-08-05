# Email Address Kit

[Русский](README.ru.md) · [English](README.md)

PHP library for parsing, validating, normalizing, comparing, and correcting email addresses.

Requirements: **PHP 7.4+**. Framework-agnostic.

**Full documentation:** [docs/ru](docs/ru/README.md) · [docs/en](docs/en/README.md)


## Installation

```bash
composer require email-address-kit/email-address-kit
```


## Quick start



### Parsing an email into local-part and domain

`Email::parse()` splits the string into parts. Invalid input does not throw — you get an object first, then check with `isValid()`.

```php
use EmailAddressKit\Email;

$email = Email::parse('Alex.Petrov+News@Ya.RU');

$email->original();              // Alex.Petrov+News@Ya.RU
$email->address()->value();      // Alex.Petrov+News
$email->domain()->value();       // Ya.RU
(string) $email;                 // alex.petrov+news@ya.ru
```

---

### Three ways to get an email string — for different jobs

| Method | What it does | When to use |
|---|---|---|
| `original()` | as entered by the user, unchanged | “what came in” logs, debugging |
| `normalized()` / `(string)` | lowercase only, no guessing | display, templates, safe printing |
| `canonical()` | mailbox key: same rules as `equals()` | UNIQUE in DB, dedup, “same mailbox” lookup |

```php
$email = Email::parse('Kate+Shop@Ya.RU');

$email->original();     // Kate+Shop@Ya.RU
$email->normalized();   // kate+shop@ya.ru
(string) $email;        // kate+shop@ya.ru  (= normalized)
$email->canonical();    // kate+shop@yandex.ru
```

Important:

- `normalized()` does **not** collapse equivalents (`ya.ru` stays `ya.ru`) and does **not** strip `+tag` / dots.
- `canonical()` collapses equivalents (`ya.ru` → `yandex.ru`) and respects comparison flags, like `equals()`:

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

Two addresses are the same mailbox ⟺ their `canonical()` values match (with the same flags).

---

### Service name from a domain

If the domain is in the provider catalog, you can get the service: a stable `id` and a human-readable `name`.

```php
$email = Email::parse('kate@ya.ru');

$email->domain()->isKnown(); // true

$service = $email->service();
// same as: $email->domain()->service()

$service->id();   // yandex
$service->name(); // Yandex
```

Examples:

```php
Email::parse('a@gmail.com')->service()->name();       // Gmail
Email::parse('a@googlemail.com')->service()->name();  // Gmail (same service)
Email::parse('a@mail.ru')->service()->name();         // Mail.ru
Email::parse('a@inbox.ru')->service()->name();        // Mail.ru
```

Unknown domain — no service:

```php
$email = Email::parse('anna@my-company.example');

$email->domain()->isKnown(); // false
$email->service();           // null
```

Same-service domains but **different** mailboxes can be told apart with `sameProviderAs` (this is not `equals`):

```php
Email::parse('a@mail.ru')->domain()->sameProviderAs('inbox.ru'); // true
Email::parse('a@mail.ru')->equals('a@inbox.ru');                 // false
```

List of all known services — `id => name` map:

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

Domain list is also available: `registry()->domains()`.

---

### Cyrillic support (IDN / EAI)

The library supports unicode in the **domain** and in the **local-part**.

#### Domain (IDN → Punycode)

Requires the PHP **`intl`** extension. A unicode domain and its ASCII form are treated as the same mailbox under `equals()`.

```php
$email = Email::parse('info@почта.рф');

$email->isValid();           // true (with intl)
$email->normalized();        // info@почта.рф
$email->domain()->value();   // почта.рф
$email->domain()->ascii();   // xn--80a1acny.xn--p1ai

Email::parse('info@почта.рф')
    ->equals('info@xn--80a1acny.xn--p1ai'); // true
```

Without `intl`, a non-ASCII **domain** → `isValid() === false`, code `INVALID_DOMAIN`.

```bash
php -m | findstr intl
# or: php -m | grep intl
```

#### Local-part (EAI)

UTF-8 letters/digits in the mailbox name are allowed and are **not** converted to Punycode. Works **without** `intl` as well.

```php
Email::parse('иван@gmail.com')->isValid();            // true
Email::parse('иван.петров@gmail.com')->isValid();     // true
Email::parse('инфо@компания.онлайн')->isValid();      // true (domain — when intl is available)
```

Local-part comparison/normalization uses unicode lowercase where possible.

---

### Syntax validation

Checks address format: presence of `@`, empty parts, illegal characters, length, domain structure.

```php
$email = Email::parse('hello@world.com');
$email->isValid(); // true

$bad = Email::parse('not-an-email');
$bad->isValid(); // false

foreach ($bad->validation()->errors() as $error) {
    $error->code();     // e.g. INVALID_FORMAT
    $error->message();
}
```

---

### Optional DNS check (MX/A)

DNS is off by default. When needed, you can verify that the domain has an MX or A record.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('nina.k@example.com');

$email->isValid(); // true; syntax only

$email->isValid(ValidationOptions::checkDns()); // syntax + MX or A on the domain

$result = $email->validation(ValidationOptions::checkDns()); // no DNS records → DNS_CHECK_FAILED
```

DNS runs only after successful syntax validation. A transient DNS failure can produce a false negative. This does not check whether the mailbox exists on the server.

---

### Comparing addresses (`equals`)

Checks whether two strings are the same mailbox: case-insensitive, with equivalent domains (`ya.ru` ↔ `yandex.ru`, `googlemail.com` ↔ `gmail.com`).

```php
Email::parse('Kate@Ya.RU')->equals('kate@yandex.ru');     // true
Email::parse('kate@mail.ru')->equals('kate@inbox.ru');    // false (same service, different mailboxes)
Email::parse('kate+news@mail.ru')->equals('kate@mail.ru'); // false (+tag is significant)

// Gmail equivalents — same mailbox:
Email::parse('kate@googlemail.com')->equals('kate@gmail.com'); // true
```

By default, dots in the local-part are significant even for Gmail. To treat `ka.te@gmail.com` and `kate@gmail.com` as the same mailbox:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

Email::parse('ka.te@gmail.com')->equals('kate@gmail.com', ComparisonOptions::ignoreGmailDots()); // true
Email::parse('ka.te@googlemail.com')->equals('kate@gmail.com', ComparisonOptions::ignoreGmailDots()); // true
Email::parse('ka.te@mail.ru')->equals('kate@mail.ru', ComparisonOptions::ignoreGmailDots()); // false (not Gmail)
```

Gmail dots and `+tag` can be combined as an array of flags:

```php
Email::parse('ka.te+news@gmail.com')->equals('kate@gmail.com', [
    ComparisonOptions::ignoreGmailDots(),
    ComparisonOptions::ignorePlusTag(),
]); // true
```

To ignore `+tag` for all providers:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

Email::parse('kate+news@mail.ru')->equals('kate@mail.ru', ComparisonOptions::ignorePlusTag()); // true
```

---

### Finding matches in an array (`filterEquals`)

Finds every address in a list (e.g. from a DB) that is the same mailbox. Keys and original strings are preserved.

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

Fine for short in-memory lists. For large datasets, store and look up by `canonical()`.

---

### Domain typo detection

The detector compares the domain against known providers and suggests close matches (e.g. `gmial.com` → `gmail.com`).

```php
$email = Email::parse('igor@gmial.com');

$email->hasSuggestions(); // true

foreach ($email->suggestions() as $suggestion) {
    $suggestion->email()->normalized(); // igor@gmail.com
    $suggestion->score();               // 0.0 … 1.0
    $suggestion->reason();              // domain_typo | domain_equivalent
}
```

`domain_typo` — likely a typo.  
`domain_equivalent` — informational suggestion to collapse to canonical (`googlemail.com` → `gmail.com`); not applied as a typo by auto-correct.

---

### Auto-correcting typos at high confidence

`correct()` applies only `domain_typo`, and only if the score is at least the threshold (default `0.95`). Otherwise the address is left unchanged.

```php
Email::parse('igor@gmial.com')->correct()->normalized();
// igor@gmail.com

Email::parse('igor@gmial.com')->correct(0.99); // custom threshold
```

---

### Detecting disposable / temporary domains

This check is separate from `isValid()`: a temporary address is often syntactically valid.

```php
Email::parse('demo@mailinator.com')->isValid();      // true
Email::parse('demo@mailinator.com')->isDisposable(); // true
Email::parse('demo@gmail.com')->isDisposable();      // false
```

The domain list is read from a compiled hash map (O(1)). Parent domains are optionally matched too: `foo.mailinator.com` is also treated as disposable. Update the list with `composer update-disposable` (or offline `--local-only`).


## Development

```bash
composer test               # PHPUnit
composer psalm              # static analysis
composer rector             # refactoring dry-run
composer compile-domains    # known-domain index
composer update-disposable  # disposable-domain index
```

Configs: `phpunit.xml`, `psalm.xml`, `rector.php`.

## License

MIT
