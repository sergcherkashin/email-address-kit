# Опечатки и автоисправление

## Suggestions

Детектор ищет похожие **известные** домены (опечатки вроде `gmial.com`, `yandex.ry`).

```php
$email = Email::parse('igor@gmial.com');

$email->hasSuggestions(); // true

foreach ($email->suggestions() as $suggestion) {
    $suggestion->email()->normalized(); // igor@gmail.com
    $suggestion->score();               // 0.0 … 1.0
    $suggestion->reason();              // domain_typo | domain_equivalent
}
```

Причины (`SuggestionReason`):

- `domain_typo` — похоже на опечатку;
- `domain_equivalent` — предложение свернуть к canonical equivalent (информативно; в auto-correct не применяется как «исправление опечатки»).

Результаты suggestions кешируются на объекте Email.

## correct

```php
$fixed = $email->correct();      // порог по умолчанию 0.95
$fixed = $email->correct(0.90);
```

Автоисправление применяется **только** к `domain_typo` при score ≥ порога.  
Иначе возвращается тот же экземпляр (или эквивалент без изменений смысла).

```php
Email::parse('igor@gmial.com')->correct()->normalized();
// igor@gmail.com

// Equivalent rewrite (googlemail → gmail) используется в equals/canonical,
// но correct() не обязан «исправлять» его как typo.
```

## Нюансы

- Опечатки в **local-part** сейчас не исправляются.
- Кандидаты берутся из справочника известных доменов; неизвестный корпоративный домен без близких соседей suggestions не получит.
- Популярные домены (Gmail и др.) имеют приоритет при близких расстояниях (например, чтобы `jmail.com` чаще вело к `gmail.com`, а не к менее ожидаемому соседу).

Далее: [Disposable](09-disposable.md)
