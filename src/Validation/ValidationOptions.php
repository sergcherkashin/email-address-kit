<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * Options that control email validation behaviour.
 *
 * DNS/MX checks are disabled by default.
 */
final class ValidationOptions
{
    private bool $checkDns;

    
    private function __construct(bool $checkDns)
    {
        $this->checkDns = $checkDns;
    }

    /**
     * Default options: syntax only, no DNS lookup.
     */
    public static function default(): self
    {
        return new self(false);
    }

    /**
     * Options that enable DNS MX/A verification for the domain.
     */
    public static function checkDns(): self
    {
        return new self(true);
    }

    /**
     * Returns a copy with an explicit DNS-check policy.
     */
    public function withCheckDns(bool $check): self
    {
        return new self($check);
    }

    /**
     * Returns whether DNS MX/A records must be verified.
     */
    public function shouldCheckDns(): bool
    {
        return $this->checkDns;
    }
}
