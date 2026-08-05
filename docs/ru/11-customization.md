# Кастомизация

## EmailFactory

Точка сборки зависимостей: registry, validator, typo, comparison strategy, disposable checker.

```php
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Comparison\GmailComparisonStrategy;

$factory = EmailFactory::fromRegistry(
    DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default()),
    new GmailComparisonStrategy()
);

EmailFactory::setDefault($factory); // влияет на Email::parse()
$email = $factory->parse('user@example.com');
```

Сброс в тестах:

```php
EmailFactory::setDefault(null);
```

## Свой реестр доменов

```php
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\EmailFactory;

$registry = new DomainRegistry();

$registry->registerProvider('mailru', 'Mail.ru', [
    'mail.ru',
    'inbox.ru',
    'list.ru',
    'bk.ru',
]);

$registry->register(
    'yandex',
    'Yandex',
    ['yandex.ru', 'ya.ru'],
    ['yandex.ru' => ['ya.ru']]
);

$factory = EmailFactory::fromRegistry($registry);
```

Можно комбинировать с `ArrayDomainSource`, `FileDomainSource`, `CompositeDomainSource`, `BuiltinCompiledDomainSource`.

## Свой disposable checker

Передайте реализацию `DisposableDomainCheckerInterface` четвёртым аргументом `fromRegistry()` или в конструктор factory.

Для тестов удобно скомпилировать крошечный map во временный файл и указать путь в `DisposableDomainChecker`.

## Своя стратегия сравнения

Реализуйте `EmailComparisonStrategyInterface`:

- `equals(Email $left, Email $right, ?ComparisonOptions $options): bool`
- `canonical(Email $email, ?ComparisonOptions $options): string`

Контракт: `equals` должно быть согласовано с `canonical` (равны ⟺ одинаковый canonical при тех же options).

Далее: [Справочник API](12-api-reference.md)
