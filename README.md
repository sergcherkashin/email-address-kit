# Email Address Kit

[Русский](README.ru.md) · [English](README.md)

PHP library for parsing, validating, normalizing, comparing, and correcting email addresses.

Requirements: **PHP 7.4+**. Framework-agnostic.

**Full documentation:** [docs/en](docs/en/README.md) · [docs/ru](docs/ru/README.md)

IDN domains (`почта.рф`, `компания.онлайн`, etc.) need the **`intl`** extension (`idn_to_ascii`).  
Without it, unicode domains yield `isValid() === false` with `INVALID_DOMAIN`.  
UTF-8 in the local-part (EAI: `иван@gmail.com`) works without `intl`.

```bash
php -m | grep intl
# Windows:
php -m | findstr intl
```

## Installation

```bash
composer require email-address-kit/email-address-kit
```

Local development:

```bash
composer install
php bin/compile-domains
```

## Quick start

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

## Features

| Feature | Description |
|---|---|
| Parsing | `Email::parse()` — invalid input does not throw |
| Validation | syntax checks with typed errors |
| Normalization | lowercase only; no typo fixes, no `+tag`/dot stripping |
| Comparison | case-insensitive + canonical; `+tag` significant by default |
| Typo detection | domain typos among known providers |
| Auto-correct | only at high confidence (`domain_typo`) |
| Disposable | `isDisposable()` — temporary domains (separate from validation) |

## Providers vs equivalents

Two different domain relationships:

**Equivalent domains** — the same mailbox:

```php
Email::parse('one@ya.ru')->equals('one@yandex.ru');           // true
Email::parse('one@googlemail.com')->equals('one@gmail.com'); // true
```

Find matches in a list (e.g. from a DB) — same rules as `equals()`, keys and original strings preserved:

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

Uniqueness key for DB / indexes (same rules as `equals()`):

```php
Email::parse('Kate@Ya.RU')->canonical();     // kate@yandex.ru
Email::parse('kate@yandex.ru')->canonical(); // kate@yandex.ru

// normalized() does not rewrite the domain:
Email::parse('Kate@Ya.RU')->normalized();    // kate@ya.ru
```

**Provider domains** — same service, different mailboxes:

```php
Email::parse('one@mail.ru')->equals('one@inbox.ru'); // false

Email::parse('one@mail.ru')
    ->domain()
    ->sameProviderAs('inbox.ru'); // true
```

More detail: [docs/en](docs/en/README.md).

## API

```php
$email = Email::parse($input);

$email->original();
$email->normalized();        // = (string) $email
$email->canonical();         // mailbox key (equals / DB)
$email->address();           // Address
$email->domain();            // Domain
$email->isValid();
$email->validation();        // ValidationResult
$email->isValid(\EmailAddressKit\Validation\ValidationOptions::checkDns());
$email->isDisposable();      // temporary / disposable domain
$email->service();           // ?EmailService
$email->hasSuggestions();
$email->suggestions();       // EmailSuggestion[]
$email->correct(0.95);       // Email
$email->equals($other);      // Email|string
$email->filterEquals($list); // matches from an array (keys preserved)
```

`(string) $email` / echo / templates always use `normalized()`: lowercase, no typo fixes, no equivalent rewrite.  
Raw input: `original()`. DB key: `canonical()`.

### Disposable domains

Check is **separate** from `isValid()`: an address can be syntactically valid and still disposable.

```php
Email::parse('user@mailinator.com')->isValid();       // true
Email::parse('user@mailinator.com')->isDisposable();  // true
Email::parse('user@gmail.com')->isDisposable();       // false
```

Runtime reads the compiled hash map `resources/compiled/disposable.php` (O(1), optional parent-domain match: `foo.mailinator.com`).

Update the list:

```bash
composer update-disposable
# or offline from seed:
php bin/update-disposable --local-only
```

Sources are configured in `resources/disposable/sources.php` — primary (default: groundcat) and fallback (local `seed.txt`).  
Real-provider allowlist: `resources/disposable/allowlist.txt`.

If an upstream is abandoned, change the URL/type in config — checker code stays untouched.

Custom checker:

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

DNS is **off** by default.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('user@example.com');

$email->isValid(); // syntax only

$email->isValid(ValidationOptions::checkDns());
// syntax + MX or A record present

$result = $email->validation(ValidationOptions::checkDns());
// missing DNS records → DNS_CHECK_FAILED
```

DNS runs only after successful syntax validation.  
Transient DNS failures can produce false negatives.

### IDN / Punycode / EAI

- **Domain:** IDN → Punycode (`intl` / `idn_to_ascii`). Unicode and ASCII forms of the same domain are equal under `equals()`.
- **Local-part:** EAI — UTF-8 letters/digits allowed, **no** Punycode.

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

Without `intl`, non-ASCII **domains** get `INVALID_DOMAIN` — expected, not a bug.  
Enable the extension in `php.ini`. UTF-8 local-part works without `intl`.

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

`correct()` applies only `domain_typo` when score ≥ threshold.  
Equivalent rewrite (`googlemail.com` → `gmail.com`) is used by `equals()` / `canonical()`, not by auto-correct.

## Comparison strategies

### DefaultComparisonStrategy

Used by default:

- lowercase;
- canonical domain from equivalents;
- **`+tag` significant** for all providers;
- dots in the local-part **kept** (significant).

```php
Email::parse('serg+news@mail.ru')->equals('serg@mail.ru'); // false
Email::parse('ser.g@gmail.com')->equals('serg@gmail.com');   // false
```

Ignore `+tag` for everyone:

```php
use EmailAddressKit\Comparison\ComparisonOptions;

$options = ComparisonOptions::ignorePlusTag();

Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options); // true
Email::parse('serg+news@gmail.com')->equals('serg@gmail.com', $options); // true
```

### GmailComparisonStrategy

On top of default: for Gmail / `googlemail.com`, dots in the local-part are ignored.

| Rule | Where | Example |
|---|---|---|
| `+tag` significant | all providers (default) | `serg+news@…` ≠ `serg@…` |
| `+tag` ignored | `ComparisonOptions::ignorePlusTag()` | `serg+news@…` = `serg@…` |
| dots ignored | Gmail strategy only | `ser.g@gmail.com` = `serg@gmail.com` |
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

// Normalization does not depend on the comparison strategy:
Email::parse('Ser.G+Tag@Gmail.Com')->normalized(); // ser.g+tag@gmail.com
```

`Email::normalized()` **never** strips dots or `+tag` — that is only for `equals()`.

## Domain catalog

Source of truth:

```text
resources/domains/providers/*.php
```

Without equivalents:

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

With equivalents:

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

Build the runtime index:

```bash
php bin/compile-domains
```

In `resources/compiled/domains.php`, field `c` (canonical) is written **only** when it differs from the domain:

```php
'mail.ru' => ['s' => 'mailru'],
'ya.ru'   => ['s' => 'yandex', 'c' => 'yandex.ru'],
```

## Custom registry

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
