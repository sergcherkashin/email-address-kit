<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * A single typed validation error.
 */
final class ValidationError
{
    private string $code;

    private string $message;

    private ?int $position;

    private ?string $value;

    
    public function __construct(string $code, string $message, ?int $position = null, ?string $value = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->position = $position;
        $this->value = $value;
    }

    /**
     * Returns the typed error code.
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Returns the human-readable message.
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Returns the optional character position in the original input.
     */
    public function position(): ?int
    {
        return $this->position;
    }

    /**
     * Returns the optional related value fragment.
     */
    public function value(): ?string
    {
        return $this->value;
    }
}
