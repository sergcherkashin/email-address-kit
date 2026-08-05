# Parsing and validation

## Parse

```php
use EmailAddressKit\Email;

$email = Email::parse('user@example.com');
// or
$email = EmailFactory::default()->parse('user@example.com');
```

Invalid input does **not** have to throw: you get an object and check `isValid()`.

```php
$email = Email::parse('not-an-email');
$email->isValid(); // false
```

## isValid and validation

```php
$email->isValid();
$result = $email->validation();
$result->isValid();
$result->errors(); // ValidationError[]
```

Each error exposes:

- `code()` — `ValidationErrorCode` constant;
- `message()`;
- optional `position()`, `value()`.

### Main error codes

| Code | Meaning |
|---|---|
| `INVALID_FORMAT` | missing `@`, multiple `@`, etc. |
| `EMPTY_ADDRESS` / `EMPTY_DOMAIN` | empty part |
| `INVALID_CHARACTER` | spaces / illegal characters |
| `INVALID_ADDRESS` / `INVALID_DOMAIN` | part syntax |
| `ADDRESS_TOO_LONG` / `DOMAIN_TOO_LONG` | length limits |
| `DNS_CHECK_FAILED` | no MX/A (DNS option only) |

Syntax validation without DNS is **cached** on the object.  
DNS-enabled checks are recomputed every time.

## DNS / MX (optional)

DNS is **off** by default.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('nina.k@example.com');

$email->isValid(); // syntax only

$email->isValid(ValidationOptions::checkDns());
// syntax + MX or A record present
```

Notes:

- DNS runs only after successful syntax validation;
- transient DNS failures can yield false negatives;
- this does not prove the mailbox exists.

## Examples

```php
Email::parse('hello@world.com')->isValid();     // true
Email::parse('@world.com')->isValid();          // false, EMPTY_ADDRESS
Email::parse('hello@')->isValid();              // false, EMPTY_DOMAIN
Email::parse('hello')->isValid();               // false, INVALID_FORMAT
Email::parse('hello@@world.com')->isValid();    // false, INVALID_FORMAT
Email::parse('hel lo@world.com')->isValid();    // false, INVALID_CHARACTER
Email::parse('hello@localhost')->isValid();     // false, INVALID_DOMAIN (single label)
```

Next: [Domains: providers and equivalents](06-domains-providers-equivalents.md)
