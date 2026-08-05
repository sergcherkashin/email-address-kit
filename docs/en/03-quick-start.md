# Quick start

```php
use EmailAddressKit\Email;

$email = Email::parse('Alex.Petrov@Gmial.com');

$email->address()->value();      // Alex.Petrov
$email->domain()->value();       // Gmial.com
$email->isValid();               // true (syntax)
$email->normalized();            // alex.petrov@gmial.com
(string) $email;                 // alex.petrov@gmial.com
$email->hasSuggestions();        // true
$email->correct()->normalized(); // alex.petrov@gmail.com
```

## Common scenarios

### Sign-up: validity + typo hint

```php
$email = Email::parse($_POST['email'] ?? '');

if (!$email->isValid()) {
    // show $email->validation()->errors()
}

if ($email->hasSuggestions()) {
    // offer $email->suggestions()[0]->email()->normalized()
}
```

### Login / dedup: same mailbox

```php
$incoming = Email::parse('maria.k@ya.ru');

// key for UNIQUE / DB lookup
$key = $incoming->canonical(); // maria.k@yandex.ru

// matches among already loaded rows
$matches = $incoming->filterEquals($emailsFromDb);
```

### Reject temporary mail

```php
if ($email->isValid() && !$email->isDisposable()) {
    // accept
}
```

Next: [Core concepts](04-concepts.md)
