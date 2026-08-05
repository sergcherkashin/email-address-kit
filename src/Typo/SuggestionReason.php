<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

/**
 * Suggestion reason codes.
 */
final class SuggestionReason
{
    public const DOMAIN_TYPO = 'domain_typo';
    public const DOMAIN_EQUIVALENT = 'domain_equivalent';

    /**
     * Prevents instantiation.
     */
    private function __construct()
    {
    }
}
