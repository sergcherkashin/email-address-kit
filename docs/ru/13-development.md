# Разработка и сборка данных

## Команды

```bash
composer test                 # PHPUnit
composer psalm                # статический анализ
composer rector               # dry-run рефакторинга
composer compile-domains      # индекс известных доменов
composer update-disposable    # индекс disposable-доменов
```

Конфиги: `phpunit.xml`, `psalm.xml`, `rector.php`.

## Справочник провайдеров

Source of truth:

```text
resources/domains/providers/*.php
resources/domains/manifest.php
```

Сборка:

```bash
php bin/compile-domains
```

Результат:

```text
resources/compiled/domains.php
resources/compiled/services.php
resources/compiled/meta.php
```

Runtime читает скомпилированные файлы через `BuiltinCompiledDomainSource`.

## Disposable-данные

```text
resources/disposable/sources.php    # primary / fallback / allowlist
resources/disposable/allowlist.txt
resources/disposable/seed.txt       # офлайн fallback
resources/compiled/disposable.php
resources/compiled/disposable-meta.php
```

```bash
php bin/update-disposable
php bin/update-disposable --local-only
```

## Структура исходников (ориентир)

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

## Тесты

Каталог `tests/Unit` и `tests/Integration`.  
IDN live-тесты могут пропускаться без `intl`; отдельные тесты с моками покрывают ветки без расширения.

## Версия 1.0 — границы

Первая версия сознательно не включает:

- SMTP-проверку mailbox;
- role-based адреса (`noreply@`, `info@`) как отдельный API;
- группировку всего списка по canonical (`groupByEquals`) — используйте `canonical()` вручную;
- автоисправление local-part.

Документация EN: [docs/en](../en/README.md)
