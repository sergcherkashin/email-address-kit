# Быстрый старт

```php
use EmailAddressKit\Email;

$email = Email::parse('Alex.Petrov@Gmial.com');

$email->address()->value();      // Alex.Petrov
$email->domain()->value();       // Gmial.com
$email->isValid();               // true (синтаксис)
$email->normalized();            // alex.petrov@gmial.com
(string) $email;                 // alex.petrov@gmial.com
$email->hasSuggestions();        // true
$email->correct()->normalized(); // alex.petrov@gmail.com
```

## Типичные сценарии

### Регистрация: валидность + опечатка

```php
$email = Email::parse($_POST['email'] ?? '');

if (!$email->isValid()) {
    // показать $email->validation()->errors()
}

if ($email->hasSuggestions()) {
    // предложить $email->suggestions()[0]->email()->normalized()
}
```

### Логин / дедуп: один mailbox

```php
$incoming = Email::parse('maria.k@ya.ru');

// ключ для UNIQUE / поиска в БД
$key = $incoming->canonical(); // maria.k@yandex.ru

// совпадения среди уже загруженных строк
$matches = $incoming->filterEquals($emailsFromDb);
```

### Отсечь временную почту

```php
if ($email->isValid() && !$email->isDisposable()) {
    // принять
}
```

Далее: [Основные понятия](04-concepts.md)
