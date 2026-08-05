# Парсинг и валидация

## Parse

```php
use EmailAddressKit\Email;

$email = Email::parse('user@example.com');
// или
$email = EmailFactory::default()->parse('user@example.com');
```

Невалидный ввод **не обязан** бросать исключение: вы получаете объект и проверяете `isValid()`.

```php
$email = Email::parse('not-an-email');
$email->isValid(); // false
```

## isValid и validation

```php
$email->isValid();                 // bool
$result = $email->validation();    // ValidationResult
$result->isValid();
$result->errors();                 // ValidationError[]
```

У каждой ошибки:

- `code()` — константа из `ValidationErrorCode`;
- `message()`;
- опционально `position()`, `value()`.

### Коды ошибок (основные)

| Код | Смысл |
|---|---|
| `INVALID_FORMAT` | нет `@`, несколько `@` и т.п. |
| `EMPTY_ADDRESS` / `EMPTY_DOMAIN` | пустая часть |
| `INVALID_CHARACTER` | пробелы и недопустимые символы |
| `INVALID_ADDRESS` / `INVALID_DOMAIN` | синтаксис части |
| `ADDRESS_TOO_LONG` / `DOMAIN_TOO_LONG` | лимиты длины |
| `DNS_CHECK_FAILED` | нет MX/A (только с опцией DNS) |

Синтаксическая проверка без DNS **кешируется** на объекте.  
Проверка с DNS каждый раз пересчитывается.

## DNS / MX (опционально)

По умолчанию DNS **не** проверяется.

```php
use EmailAddressKit\Validation\ValidationOptions;

$email = Email::parse('nina.k@example.com');

$email->isValid(); // только синтаксис

$email->isValid(ValidationOptions::checkDns());
// синтаксис + наличие MX или A у домена
```

Замечания:

- DNS идёт только после успешного синтаксиса;
- временный сбой DNS → возможный ложноотрицательный результат;
- это не проверка «ящик существует».

## Примеры

```php
Email::parse('hello@world.com')->isValid();     // true
Email::parse('@world.com')->isValid();          // false, EMPTY_ADDRESS
Email::parse('hello@')->isValid();              // false, EMPTY_DOMAIN
Email::parse('hello')->isValid();               // false, INVALID_FORMAT
Email::parse('hello@@world.com')->isValid();    // false, INVALID_FORMAT
Email::parse('hel lo@world.com')->isValid();    // false, INVALID_CHARACTER
Email::parse('hello@localhost')->isValid();     // false, INVALID_DOMAIN (одна метка)
```

Далее: [Домены: providers и equivalents](06-domains-providers-equivalents.md)
