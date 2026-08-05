# Установка

## Требования

- PHP **7.4** или **8.x**
- Рекомендуется расширение **`intl`** для IDN-доменов (`почта.рф` и т.п.)

Проверка `intl`:

```bash
php -m | findstr intl
# или
php -m | grep intl
```

Без `intl`:

- unicode-**домены** → `isValid() === false`, код `INVALID_DOMAIN`;
- UTF-8 в **local-part** (EAI) работает и без `intl`.

## Composer

```bash
composer require email-address-kit/email-address-kit
```

## Локальная разработка репозитория

```bash
composer install
php bin/compile-domains
# опционально:
composer update-disposable
# или офлайн:
php bin/update-disposable --local-only
```

## Автозагрузка

Namespace: `EmailAddressKit\` → каталог `src/`.

```php
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
```

Далее: [Быстрый старт](03-quick-start.md)
