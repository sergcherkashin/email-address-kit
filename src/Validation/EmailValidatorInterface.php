<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

use EmailAddressKit\Email;

/**
 * Contract for email validation.
 */
interface EmailValidatorInterface
{
    /**
     * Validates an email object.
     */
    public function validate(Email $email, ?ValidationOptions $options = null): ValidationResult;
}
