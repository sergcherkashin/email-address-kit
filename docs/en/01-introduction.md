# Introduction

## Why this library

Email Address Kit helps at input boundaries (forms, APIs, imports, databases):

- split a string into local-part and domain;
- validate syntax (and optionally DNS);
- normalize safely;
- decide whether two strings are the same mailbox (`ya.ru` ↔ `yandex.ru`);
- find matches in a DB-loaded list;
- produce a uniqueness key for indexes;
- suggest domain typo fixes (`gmial.com` → `gmail.com`);
- flag disposable domains separately.

The library does **not** send mail, talk SMTP, or verify that a mailbox exists on the server.

## Design principles

1. **Parse does not throw on a “bad” email** — you get an object, then call `isValid()`.
2. **Normalization ≠ correction** — lowercase only, no guessing user intent.
3. **Comparison ≠ normalization** — `equals()` / `canonical()` may rewrite equivalents and follow a strategy.
4. **Disposable ≠ invalid** — a temporary address can be syntactically valid.
5. **Framework-free** — plain PHP; wire dependencies via `EmailFactory`.

## Model

```text
Email
├── Address   (local-part)
└── Domain
```

`Email` is a facade; validation, typo detection, comparison, and disposable checks are injected by the factory.

## In scope / out of scope

| In scope | Out of scope |
|---|---|
| Parse, validate, normalize | SMTP / IMAP / POP3 |
| equals, filterEquals, canonical | “Does this mailbox exist?” |
| Domain typo / correct | Sending email |
| Provider domain catalog | Full reputation / WHOIS |
| Disposable checks | Blocking disposable inside `isValid()` |
| IDN domains (with `intl`), EAI local-part | |

Next: [Installation](02-installation.md)
