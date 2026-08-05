<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

use EmailAddressKit\Service\EmailService;
use EmailAddressKit\Support\IdnConverter;
use EmailAddressKit\Support\IdnConverterInterface;

/**
 * Represents the domain part of an email address.
 *
 * Holds only a snapshot of DomainInfo for known domains — never the full registry.
 */
final class Domain
{
    private string $value;

    private ?DomainInfo $info;

    private IdnConverterInterface $idn;

    
    public function __construct(string $value, ?DomainInfo $info = null, ?IdnConverterInterface $idn = null)
    {
        $this->value = $value;
        $this->info = $info;
        $this->idn = $idn ?? new IdnConverter();
    }

    /**
     * Returns the original domain value.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Returns the safely normalized domain (UTF-8 lowercase when possible).
     *
     * Does not convert IDN to Punycode.
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
     * Returns the ASCII/Punycode domain used for DNS, registry and equality.
     */
    public function ascii(): string
    {
        $ascii = $this->idn->toAscii($this->value);

        return $ascii ?? $this->normalized();
    }

    /**
     * Returns the domain name without the TLD.
     *
     * For "mail.google.com" returns "mail.google".
     */
    public function name(): string
    {
        $normalized = $this->normalized();
        $parts = \explode('.', $normalized);

        if (\count($parts) < 2) {
            return $normalized;
        }

        \array_pop($parts);

        return \implode('.', $parts);
    }

    /**
     * Returns the top-level domain label.
     */
    public function tld(): string
    {
        $normalized = $this->normalized();
        $parts = \explode('.', $normalized);

        return $parts[\count($parts) - 1];
    }

    /**
     * Returns the canonical domain used for mailbox equality.
     *
     * Unknown domains use the ASCII/Punycode form.
     */
    public function canonical(): string
    {
        if (!$this->info instanceof DomainInfo) {
            return $this->ascii();
        }

        return $this->info->canonical();
    }

    /**
     * Checks whether the domain is present in the registry.
     */
    public function isKnown(): bool
    {
        return $this->info instanceof DomainInfo;
    }

    /**
     * Returns the provider service for this domain, if known.
     */
    public function service(): ?EmailService
    {
        return $this->info instanceof DomainInfo ? $this->info->service() : null;
    }

    /**
     * Returns domains that share the same mailbox.
     *
     * @return string[]
     */
    public function equivalents(): array
    {
        if (!$this->info instanceof DomainInfo) {
            return [$this->ascii()];
        }

        return $this->info->equivalents();
    }

    /**
     * Returns domains that belong to the same provider.
     *
     * @return string[]
     */
    public function providerDomains(): array
    {
        if (!$this->info instanceof DomainInfo) {
            return [];
        }

        return $this->info->providerDomains();
    }

    /**
     * Checks whether another domain belongs to the same provider.
     *
     * Accepted values: Domain instance or domain string.
     *
     * @param mixed $other Domain object or domain string.
     */
    public function sameProviderAs($other): bool
    {
        if ($other instanceof self) {
            $otherDomain = $other->ascii();
        } elseif (\is_string($other)) {
            $otherAscii = $this->idn->toAscii($other);
            if ($otherAscii === null) {
                return false;
            }
            $otherDomain = $otherAscii;
        } else {
            return false;
        }

        if (!$this->info instanceof DomainInfo) {
            return false;
        }

        return $this->info->sameProviderAs($otherDomain);
    }

    /**
     * Limits var_dump / print_r output to domain identity data.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $service = $this->service();

        return [
            'value' => $this->value,
            'normalized' => $this->normalized(),
            'ascii' => $this->ascii(),
            'canonical' => $this->canonical(),
            'isKnown' => $this->isKnown(),
            'service' => $service instanceof EmailService ? $service->id() : null,
        ];
    }
}
