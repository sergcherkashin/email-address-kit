<?php

declare(strict_types=1);

namespace EmailAddressKit\Address;

use EmailAddressKit\Support\IdnConverter;
use EmailAddressKit\Support\IdnConverterInterface;

/**
 * Represents the local-part of an email address (the part before "@").
 *
 * Supports EAI: UTF-8 letters/digits are allowed and are not converted via Punycode.
 */
final class Address
{
    private string $value;

    private IdnConverterInterface $idn;

    public function __construct(string $value, ?IdnConverterInterface $idn = null)
    {
        $this->value = $value;
        $this->idn = $idn ?? new IdnConverter();
    }

    /**
     * Returns the original local-part value.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Returns the safely normalized local-part (UTF-8 lowercase when possible).
     *
     * Does not remove plus-tags or dots and does not convert to Punycode.
     */
    public function normalized(): string
    {
        return $this->idn->toLower($this->value);
    }

    public function __toString(): string
    {
        return $this->normalized();
    }

    /**
     * Compares this local-part with another address in a case-insensitive way.
     *
     * Accepted values: Address instance or raw local-part string.
     * UTF-8 local-parts are compared after unicode-aware lowercasing.
     *
     * @param mixed $other Address object or raw local-part string.
     */
    public function equals($other): bool
    {
        if ($other instanceof self) {
            return $this->normalized() === $other->normalized();
        }

        if (!\is_string($other)) {
            return false;
        }

        return $this->normalized() === $this->idn->toLower($other);
    }

    /**
     * Limits var_dump / print_r output.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'normalized' => $this->normalized(),
        ];
    }
}
