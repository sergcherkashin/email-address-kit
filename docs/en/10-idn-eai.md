# IDN, Punycode, and EAI

## Domain (IDN)

Internationalized domains are converted to ASCII (Punycode) via `idn_to_ascii` (**requires `intl`**).

```php
$email = Email::parse('info@почта.рф');

$email->isValid();           // true (with intl)
$email->normalized();        // info@почта.рф  (UTF-8 lowercase)
$email->domain()->ascii();   // xn--80a1acny.xn--p1ai

Email::parse('info@почта.рф')
    ->equals('info@xn--80a1acny.xn--p1ai'); // true
```

Without `intl`, a non-ASCII domain → `INVALID_DOMAIN`. This is expected.

## Local-part (EAI)

UTF-8 letters/digits in the local-part are **allowed** and are **not** Punycode-encoded. Works without `intl`.

```php
Email::parse('иван@gmail.com')->isValid();           // true
Email::parse('иван.петров@gmail.com')->isValid();    // true
Email::parse('инфо@компания.онлайн')->isValid();     // true with intl
```

Lowercasing for compare/normalize is unicode-aware where possible.

## Practical tips

- DNS and the registry use `domain()->ascii()`.
- UIs usually show `normalized()` (human-readable UTF-8 domain).
- For IDN domains, `canonical()` uses the ASCII domain form through the same comparison pipeline when available.

Next: [Customization](11-customization.md)
