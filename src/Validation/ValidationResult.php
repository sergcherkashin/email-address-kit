<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * Result of email syntax validation.
 */
final class ValidationResult
{
    /** @var ValidationError[] */
    private array $errors;

    /**
     * @param ValidationError[] $errors List of validation errors.
     */
    public function __construct(array $errors = [])
    {
        $this->errors = \array_values($errors);
    }

    /**
     * Returns whether validation passed.
     */
    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * Returns all validation errors.
     *
     * @return ValidationError[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
