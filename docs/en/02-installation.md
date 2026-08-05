# Installation

## Requirements

- PHP **7.4** or **8.x**
- Recommended: **`intl`** for IDN domains (e.g. `почта.рф`)

Check `intl`:

```bash
php -m | grep intl
# Windows:
php -m | findstr intl
```

Without `intl`:

- non-ASCII **domains** → `isValid() === false` with `INVALID_DOMAIN`;
- UTF-8 **local-part** (EAI) still works without `intl`.

## Composer

```bash
composer require email-address-kit/email-address-kit
```

## Local repository setup

```bash
composer install
php bin/compile-domains
# optional:
composer update-disposable
# or offline:
php bin/update-disposable --local-only
```

## Autoloading

Namespace: `EmailAddressKit\` → `src/`.

```php
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
```

Next: [Quick start](03-quick-start.md)
