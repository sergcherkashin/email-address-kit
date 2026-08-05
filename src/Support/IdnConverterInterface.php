<?php

declare(strict_types=1);

namespace EmailAddressKit\Support;

/**
 * Converts internationalized domain names / labels to ASCII (Punycode).
 */
interface IdnConverterInterface
{
    /**
     * Returns whether the value contains non-ASCII characters.
     */
    public function containsNonAscii(string $value): bool;

    /**
     * Lowercases a value in a UTF-8-aware way when possible.
     */
    public function toLower(string $value): string;

    /**
     * Converts a domain or label to ASCII/Punycode.
     *
     * Pure ASCII values are returned lowercased.
     * Returns null when the value contains non-ASCII characters but cannot be converted
     * (missing intl extension or invalid IDN).
     */
    public function toAscii(string $value): ?string;
}
