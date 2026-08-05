# Домены: providers и equivalents

В библиотеке два разных отношения между доменами. Их нельзя путать.

## Equivalent domains — один mailbox

Один и тот же ящик доступен под разными доменами провайдера.

```php
Email::parse('anna@ya.ru')->equals('anna@yandex.ru');                 // true
Email::parse('anna@googlemail.com')->equals('anna@gmail.com');       // true

Email::parse('anna@ya.ru')->canonical();       // anna@yandex.ru
Email::parse('anna@yandex.ru')->canonical();   // anna@yandex.ru
```

В данных провайдера:

```php
return [
    'id' => 'yandex',
    'name' => 'Yandex',
    'domains' => ['yandex.ru', 'ya.ru'],
    'equivalents' => [
        'yandex.ru' => ['ya.ru'],
    ],
];
```

В скомпилированном индексе поле `c` (canonical) пишется **только** если отличается от самого домена:

```php
'yandex.ru' => ['s' => 'yandex'],
'ya.ru'     => ['s' => 'yandex', 'c' => 'yandex.ru'],
```

## Provider domains — один сервис, разные ящики

Несколько доменов одного бренда, но **разные** mailbox.

```php
Email::parse('anna@mail.ru')->equals('anna@inbox.ru'); // false

Email::parse('anna@mail.ru')
    ->domain()
    ->sameProviderAs('inbox.ru'); // true

$email->service()->id();   // mailru
$email->service()->name(); // Mail.ru
```

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
    // без equivalents
];
```

## Сводная таблица

| Отношение | `equals` / `canonical` | `sameProviderAs` | Пример |
|---|---|---|---|
| Equivalents | да, один mailbox | да (тот же service) | `ya.ru` ↔ `yandex.ru` |
| Provider only | нет | да | `mail.ru` ↔ `inbox.ru` |
| Разные сервисы | нет | нет | `gmail.com` ↔ `mail.ru` |

## Domain API

```php
$d = Email::parse('anna@ya.ru')->domain();

$d->isKnown();          // true
$d->canonical();        // yandex.ru
$d->equivalents();      // соседние equivalent-домены
$d->providerDomains();  // все домены сервиса
$d->sameProviderAs('yandex.ru'); // true
```

Неизвестный домен:

```php
$d = Email::parse('anna@my-company.example')->domain();
$d->isKnown();       // false
$d->canonical();     // my-company.example (сам себе)
$d->service();       // null
```

Источник данных: `resources/domains/providers/*.php` → сборка `php bin/compile-domains` → `resources/compiled/domains.php`.

Далее: [Сравнение адресов](07-comparison.md)
