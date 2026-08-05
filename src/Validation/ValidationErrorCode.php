<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * Typed validation error codes.
 */
final class ValidationErrorCode
{
    public const EMPTY_ADDRESS = 'EMPTY_ADDRESS';
    public const EMPTY_DOMAIN = 'EMPTY_DOMAIN';
    public const INVALID_ADDRESS = 'INVALID_ADDRESS';
    public const INVALID_DOMAIN = 'INVALID_DOMAIN';
    public const INVALID_CHARACTER = 'INVALID_CHARACTER';
    public const INVALID_FORMAT = 'INVALID_FORMAT';
    public const ADDRESS_TOO_LONG = 'ADDRESS_TOO_LONG';
    public const DOMAIN_TOO_LONG = 'DOMAIN_TOO_LONG';
    public const DNS_CHECK_FAILED = 'DNS_CHECK_FAILED';

    /**
     * Prevents instantiation.
     */
    private function __construct()
    {
    }
}
