# Disposable (временные) домены

## Зачем отдельно от isValid

Временный адрес часто **синтаксически валиден**:

```php
Email::parse('demo@mailinator.com')->isValid();      // true
Email::parse('demo@mailinator.com')->isDisposable(); // true
Email::parse('demo@gmail.com')->isDisposable();      // false
```

Решение «принимать или нет» — политика приложения, не часть синтаксической валидации.

## API

```php
$email->isDisposable(); // bool

use EmailAddressKit\Disposable\DisposableDomainChecker;

$checker = DisposableDomainChecker::default();
$checker->isDisposable('demo@mailinator.com');
$checker->isDisposableDomain('mailinator.com');
```

Кастомный checker в factory:

```php
$factory = EmailFactory::fromRegistry(
    EmailFactory::default()->registry(),
    null,
    null,
    DisposableDomainChecker::default()
);
```

## Как устроено

1. Upstream-списки (GitHub/HTTP/local) компилируются в `resources/compiled/disposable.php`.
2. Runtime — hash-map `домен => true` для `isset` (**O(1)**).
3. Значения `true` — заглушка: в PHP нет отдельного Set; смотрим наличие ключа.
4. Опционально проверяются **родительские** домены: `foo.mailinator.com` → match `mailinator.com`.
5. Загрузка map **ленивая** — при первом вызове `isDisposable()`.
6. Allowlist реальных провайдеров (`gmail.com`, `mail.ru`, …) вычитается на этапе компиляции.

Почему не просто `['mailinator.com', …]` + `in_array`: при тысячах доменов и обходе родителей `isset` по ключу заметно быстрее. Формат hash-map оставлен сознательно.

## Обновление списка

Конфиг источников: `resources/disposable/sources.php`.

```bash
composer update-disposable
# primary (по умолчанию groundcat) + fallback seed

php bin/update-disposable --local-only
# только локальный seed, без сети
```

Allowlist: `resources/disposable/allowlist.txt`.  
Офлайн-seed: `resources/disposable/seed.txt`.

Если upstream забросят — меняете URL/тип в `sources.php`, код checker’а не трогаете.

Поддерживаемые типы источников: `http`, `github-raw`, `local`, `composite`.

Далее: [IDN, Punycode и EAI](10-idn-eai.md)
