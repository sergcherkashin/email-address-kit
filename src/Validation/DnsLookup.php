<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

/**
 * DNS lookup based on checkdnsrr() and dns_get_record().
 *
 * domain is valid when MX or A exists.
 */
final class DnsLookup implements DnsLookupInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasMxOrARecord(string $domain): bool
    {
        return $this->hasRecord($domain, true) || $this->hasRecord($domain, false);
    }

    /**
     * Checks a single DNS record type for the domain.
     */
    private function hasRecord(string $domain, bool $isMx): bool
    {
        $normalizedDomain = $domain . '.';
        $type = $isMx ? 'MX' : 'A';

        if (!\checkdnsrr($normalizedDomain, $type)) {
            return false;
        }

        try {
            $records = \dns_get_record($normalizedDomain, $isMx ? DNS_MX : DNS_A);
        } catch (\Throwable $exception) {
            return false;
        }

        return \is_array($records) && $records !== [];
    }
}
