# Customization

## EmailFactory

Composition root for registry, validator, typo stack, comparison strategy, disposable checker.

```php
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Domain\BuiltinCompiledDomainSource;

$factory = EmailFactory::fromRegistry(
    DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default())
);

EmailFactory::setDefault($factory); // affects Email::parse()
$email = $factory->parse('user@example.com');
```

Reset in tests:

```php
EmailFactory::setDefault(null);
```

## Custom domain registry

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

You can also combine `ArrayDomainSource`, `FileDomainSource`, `CompositeDomainSource`, and `BuiltinCompiledDomainSource`.

## Custom disposable checker

Pass a `DisposableDomainCheckerInterface` as the fourth argument to `fromRegistry()` or into the factory constructor.

For tests, compile a tiny map into a temp file and point `DisposableDomainChecker` at it.

## Custom comparison strategy

Implement `EmailComparisonStrategyInterface`:

- `equals(Email $left, Email $right, ?ComparisonOptions $options): bool`
- `canonical(Email $email, ?ComparisonOptions $options): string`

Contract: `equals` must agree with `canonical` (equal ⟺ same canonical under the same options).

Next: [API reference](12-api-reference.md)
