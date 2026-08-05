# API reference

Public entry points at a glance. Details live in the topic chapters.

## Email

```php
Email::parse(string $input): Email

$email->original(): string
$email->normalized(): string
$email->__toString(): string          // = normalized()
$email->canonical($options = null): string // ComparisonOptions|ComparisonOptions[]|null

$email->address(): Address
$email->domain(): Domain

$email->isValid(?ValidationOptions $options = null): bool
$email->validation(?ValidationOptions $options = null): ValidationResult
$email->isDisposable(): bool

$email->service(): ?EmailService
$email->hasSuggestions(): bool
$email->suggestions(): EmailSuggestion[]
$email->correct(float $minConfidence = 0.95): Email

$email->equals($other, $options = null): bool // options: ComparisonOptions|ComparisonOptions[]|null
$email->filterEquals(array $emails, $options = null): array
```

## Address

```php
$address->value(): string
$address->normalized(): string
$address->__toString(): string   // = normalized()
$address->equals($other): bool
```

## Domain

```php
$domain->value(): string
$domain->normalized(): string
$domain->__toString(): string    // = normalized()
$domain->ascii(): string
$domain->name(): string
$domain->tld(): string
$domain->canonical(): string
$domain->isKnown(): bool
$domain->service(): ?EmailService
$domain->equivalents(): string[]
$domain->providerDomains(): string[]
$domain->sameProviderAs($other): bool
```

## DomainRegistry

```php
$registry = EmailFactory::default()->registry();

$registry->domains();   // string[] of known domains
$registry->services();  // array<string, string> id => name
$registry->find(string $domain): ?DomainInfo
$registry->canonical(string $domain): string
```

## EmailFactory

```php
EmailFactory::default(): self
EmailFactory::setDefault(?self $factory): void
EmailFactory::fromRegistry(
    DomainRegistryInterface $registry,
    ?EmailComparisonStrategyInterface $comparisonStrategy = null,
    ?EmailValidatorInterface $validator = null,
    ?DisposableDomainCheckerInterface $disposableChecker = null
): self

$factory->parse(string $input): Email
$factory->registry(): DomainRegistryInterface
$factory->disposableChecker(): DisposableDomainCheckerInterface
```

## Comparison

```php
ComparisonOptions::default()
ComparisonOptions::ignorePlusTag()
ComparisonOptions::ignoreGmailDots()
ComparisonOptions::resolve($options) // null | ComparisonOptions | ComparisonOptions[]

DefaultComparisonStrategy
```

## Validation

```php
ValidationOptions::default()
ValidationOptions::checkDns()

ValidationResult::isValid(): bool
ValidationResult::errors(): ValidationError[]

ValidationError::code(): string
ValidationError::message(): string
ValidationError::position(): ?int
ValidationError::value(): ?string
```

## Disposable

```php
DisposableDomainChecker::default(bool $checkParentDomains = true): self
$checker->isDisposable($email): bool
$checker->isDisposableDomain(string $domain): bool
```

## Typo

```php
EmailSuggestion::email(): Email
EmailSuggestion::score(): float
EmailSuggestion::reason(): string
```

Next: [Development and data builds](13-development.md)
