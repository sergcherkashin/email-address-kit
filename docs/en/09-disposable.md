# Disposable domains

## Why separate from isValid

A temporary address is often **syntactically valid**:

```php
Email::parse('demo@mailinator.com')->isValid();      // true
Email::parse('demo@mailinator.com')->isDisposable(); // true
Email::parse('demo@gmail.com')->isDisposable();      // false
```

Accept/reject policy belongs to the application, not to syntax validation.

## API

```php
$email->isDisposable(); // bool

use EmailAddressKit\Disposable\DisposableDomainChecker;

$checker = DisposableDomainChecker::default();
$checker->isDisposable('demo@mailinator.com');
$checker->isDisposableDomain('mailinator.com');
```

Custom checker via factory:

```php
$factory = EmailFactory::fromRegistry(
    EmailFactory::default()->registry(),
    null,
    null,
    DisposableDomainChecker::default()
);
```

## How it works

1. Upstream lists (GitHub/HTTP/local) compile into `resources/compiled/disposable.php`.
2. Runtime uses a hash map `domain => true` for `isset` (**O(1)**).
3. Values of `true` are placeholders — PHP has no native Set; only key presence matters.
4. Optionally matches **parent** domains: `foo.mailinator.com` → hits `mailinator.com`.
5. Map load is **lazy** — on the first `isDisposable()` call.
6. An allowlist of real providers (`gmail.com`, `mail.ru`, …) is subtracted at compile time.

Why not a plain list + `in_array`: with thousands of domains and parent walks, keyed `isset` is much faster. The hash-map format is intentional.

## Updating the list

Source config: `resources/disposable/sources.php`.

```bash
composer update-disposable
# primary (default: groundcat) + fallback seed

php bin/update-disposable --local-only
# local seed only, no network
```

Allowlist: `resources/disposable/allowlist.txt`.  
Offline seed: `resources/disposable/seed.txt`.

If an upstream is abandoned, change the URL/type in `sources.php` — checker code stays untouched.

Supported source types: `http`, `github-raw`, `local`, `composite`.

Next: [IDN, Punycode, and EAI](10-idn-eai.md)
