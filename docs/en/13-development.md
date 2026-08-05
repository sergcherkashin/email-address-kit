# Development and data builds

## Commands

```bash
composer test                 # PHPUnit
composer psalm                # static analysis
composer rector               # refactoring dry-run
composer compile-domains      # known-domain index
composer update-disposable    # disposable-domain index
```

Configs: `phpunit.xml`, `psalm.xml`, `rector.php`.

## Provider catalog

Source of truth:

```text
resources/domains/providers/*.php
resources/domains/manifest.php
```

Build:

```bash
php bin/compile-domains
```

Output:

```text
resources/compiled/domains.php
resources/compiled/services.php
resources/compiled/meta.php
```

Runtime loads compiled files via `BuiltinCompiledDomainSource`.

## Disposable data

```text
resources/disposable/sources.php    # primary / fallback / allowlist
resources/disposable/allowlist.txt
resources/disposable/seed.txt       # offline fallback
resources/compiled/disposable.php
resources/compiled/disposable-meta.php
```

```bash
php bin/update-disposable
php bin/update-disposable --local-only
```

## Source layout (overview)

```text
src/
  Email.php
  EmailFactory.php
  Address/
  Domain/
  Comparison/
  Validation/
  Typo/
  Disposable/
  Provider/
  Service/
  Support/
  Exception/
```

## Tests

See `tests/Unit` and `tests/Integration`.  
Live IDN tests may skip without `intl`; mock-based tests cover branches without the extension.

## Version 1.0 boundaries

Intentionally not included yet:

- SMTP mailbox probing;
- role-based addresses (`noreply@`, `info@`) as a dedicated API;
- grouping a whole list by canonical (`groupByEquals`) — use `canonical()` yourself;
- local-part auto-correct.

Russian docs: [docs/ru](../ru/README.md)
