<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

use EmailAddressKit\Service\EmailService;

/**
 * Holds resolved metadata for a known domain.
 */
final class DomainInfo
{
    private string $domain;

    private string $canonical;

    private ?EmailService $service;

    /** @var string[] */
    private array $equivalents;

    /** @var string[] */
    private array $providerDomains;

    /**
     * @param string[] $equivalents     Equivalent domain names for the same mailbox.
     * @param string[] $providerDomains All domains of the same provider service.
     */
    public function __construct(
        string $domain,
        string $canonical,
        ?EmailService $service,
        array $equivalents,
        array $providerDomains
    ) {
        $this->domain = $domain;
        $this->canonical = $canonical;
        $this->service = $service;
        $this->equivalents = \array_values($equivalents);
        $this->providerDomains = \array_values($providerDomains);
    }

    /**
     * Returns the domain this info describes.
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * Returns the canonical domain used for mailbox equality.
     */
    public function canonical(): string
    {
        return $this->canonical;
    }

    /**
     * Returns the provider service, if any.
     */
    public function service(): ?EmailService
    {
        return $this->service;
    }

    /**
     * Returns domains that represent the same mailbox.
     *
     * @return string[]
     */
    public function equivalents(): array
    {
        return $this->equivalents;
    }

    /**
     * Returns domains that belong to the same provider.
     *
     * @return string[]
     */
    public function providerDomains(): array
    {
        return $this->providerDomains;
    }

    /**
     * Checks whether another domain is in the same equivalent group.
     */
    public function isEquivalentTo(string $other): bool
    {
        $normalized = \strtolower($other);

        return \in_array($normalized, $this->equivalents, true);
    }

    /**
     * Checks whether another domain belongs to the same provider.
     */
    public function sameProviderAs(string $other): bool
    {
        $normalized = \strtolower($other);

        return \in_array($normalized, $this->providerDomains, true);
    }

    /**
     * Limits var_dump / print_r output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'domain' => $this->domain,
            'canonical' => $this->canonical,
            'service' => $this->service instanceof EmailService ? $this->service->id() : null,
            'equivalents' => $this->equivalents,
            'providerDomains' => $this->providerDomains,
        ];
    }
}
