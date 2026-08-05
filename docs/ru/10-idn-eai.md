# IDN, Punycode и EAI

## Домен (IDN)

Международные домены приводятся к ASCII (Punycode) через `idn_to_ascii` (**нужен `intl`**).

```php
$email = Email::parse('info@почта.рф');

$email->isValid();           // true (с intl)
$email->normalized();        // info@почта.рф  (UTF-8 lowercase)
$email->domain()->ascii();   // xn--80a1acny.xn--p1ai

Email::parse('info@почта.рф')
    ->equals('info@xn--80a1acny.xn--p1ai'); // true
```

Без `intl` не-ASCII домен → `INVALID_DOMAIN`. Это ожидаемо.

## Local-part (EAI)

UTF-8 буквы/цифры в local-part **допустимы** и **не** конвертируются в Punycode. Работает и без `intl`.

```php
Email::parse('иван@gmail.com')->isValid();           // true
Email::parse('иван.петров@gmail.com')->isValid();    // true
Email::parse('инфо@компания.онлайн')->isValid();     // true при intl
```

Lowercase для сравнения/нормализации — unicode-aware, где возможно.

## Практические советы

- Для DNS и registry библиотека опирается на `domain()->ascii()`.
- В UI обычно показывают `normalized()` (человекочитаемый UTF-8 домен).
- В ключах БД для IDN-доменов `canonical()` использует ASCII-форму домена там, где она доступна через ту же цепочку сравнения.

Далее: [Кастомизация](11-customization.md)
