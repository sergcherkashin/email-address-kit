# Основные понятия

## Четыре состояния адреса

```text
raw        →  то, что ввёл пользователь
parsed     →  разобрано на Address + Domain
normalized →  безопасный lowercase без догадок
corrected  →  предположительное исправление опечатки
```

Пример:

| Состояние | Значение |
|---|---|
| raw / `original()` | `Alex.Petrov+News@Ya.RU` |
| parsed | Address=`Alex.Petrov+News`, Domain=`Ya.RU` |
| `normalized()` | `alex.petrov+news@ya.ru` |
| `correct()` (если typo в домене) | может заменить только домен при высокой уверенности |

**Нормализация никогда не:**

- исправляет опечатки;
- удаляет `+tag`;
- удаляет точки в local-part;
- сводит `ya.ru` → `yandex.ru`.

## Три строковых представления Email

| Метод | Назначение | Пример для `Alex@Ya.RU` |
|---|---|---|
| `original()` | сырой ввод | `Alex@Ya.RU` |
| `normalized()` / `(string)` | логи, шаблоны, отображение | `alex@ya.ru` |
| `canonical()` | ключ mailbox для БД / equals | `alex@yandex.ru` |

```php
$email = Email::parse('Alex@Ya.RU');

$email->original();    // Alex@Ya.RU
$email->normalized();  // alex@ya.ru
(string) $email;       // alex@ya.ru
$email->canonical();   // alex@yandex.ru
```

`__toString()` у `Email`, `Address` и `Domain` всегда равен `normalized()` соответствующего объекта.

## Почему canonical отдельно

`equals('a@ya.ru', 'a@yandex.ru') === true`, но `normalized()` оставляет домен как введён.

Для UNIQUE-индекса и поиска в БД нужен стабильный ключ → `canonical()`.

Он считается **той же стратегией сравнения**, что и `equals()`:

- default: lowercase local-part + canonical domain;
- с `ComparisonOptions::ignorePlusTag()` — без `+tag`;
- с `GmailComparisonStrategy` — для Gmail ещё без точек в local-part.

## Address и Domain

```php
$email->address()->value();       // как введено
$email->address()->normalized();  // lowercase (EAI-aware)

$email->domain()->value();
$email->domain()->normalized();   // UTF-8 lowercase
$email->domain()->ascii();        // Punycode / ASCII для DNS и registry
$email->domain()->canonical();    // только домен (ya.ru → yandex.ru)
$email->domain()->isKnown();
$email->domain()->service();      // ?EmailService
$email->domain()->equivalents();
$email->domain()->providerDomains();
$email->domain()->sameProviderAs($other);
```

Важно: `Email::canonical()` — **полный** ключ `local@domain`.  
`Domain::canonical()` — **только** доменная часть.

## Debug (`var_dump`)

`__debugInfo()` показывает identity-поля, включая вычисленные `isValid` и `isDisposable`.

Нюанс: список disposable подгружается лениво при **первом** `isDisposable()`.  
`var_dump($email)` вызывает его внутри debug и может прогреть большой map — это нормально для отладки; в боевом коде без вызова `isDisposable()` файл не грузится.

Далее: [Парсинг и валидация](05-parsing-validation.md)
