<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * Looks up DNS records required to decide whether a mail domain exists.
 */
interface DnsLookupInterface
{
    /**
     * Returns whether the domain has an MX or A DNS record.
     */
    public function hasMxOrARecord(string $domain): bool;
}
