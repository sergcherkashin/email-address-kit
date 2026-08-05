# Typos and auto-correct

## Suggestions

The detector looks for similar **known** domains (typos like `gmial.com`, `yandex.ry`).

```php
$email = Email::parse('igor@gmial.com');

$email->hasSuggestions(); // true

foreach ($email->suggestions() as $suggestion) {
    $suggestion->email()->normalized(); // igor@gmail.com
    $suggestion->score();               // 0.0 … 1.0
    $suggestion->reason();              // domain_typo | domain_equivalent
}
```

Reasons (`SuggestionReason`):

- `domain_typo` — likely typo;
- `domain_equivalent` — informational collapse toward a canonical equivalent (not applied as a typo fix by auto-correct).

Suggestion results are cached on the Email instance.

## correct

```php
$fixed = $email->correct();      // default threshold 0.95
$fixed = $email->correct(0.90);
```

Auto-correct applies **only** to `domain_typo` when score ≥ threshold.  
Otherwise the same logical address is returned unchanged.

```php
Email::parse('igor@gmial.com')->correct()->normalized();
// igor@gmail.com

// Equivalent rewrite (googlemail → gmail) is used by equals/canonical,
// but correct() does not treat it as a typo fix.
```

## Nuances

- Local-part typos are not corrected today.
- Candidates come from the known-domain catalog; an unknown corporate domain with no close neighbors will get no suggestions.
- Popular domains (Gmail, etc.) get a popularity boost when distances are close (e.g. so `jmail.com` prefers `gmail.com`).

Next: [Disposable domains](09-disposable.md)
