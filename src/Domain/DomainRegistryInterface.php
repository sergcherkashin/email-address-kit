<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Contract for the domain registry that stores providers and equivalents.
 */
interface DomainRegistryInterface
{
    /**
     * Finds metadata for a domain.
     */
    public function find(string $domain): ?DomainInfo;

    /**
     * Returns the canonical domain for mailbox equality.
     *
     * Unknown domains are returned as themselves (lowercased).
     */
    public function canonical(string $domain): string;

    /**
     * Registers a provider and its domains.
     *
     * Each domain becomes its own canonical unless equivalents are registered later.
     */
    public function registerProvider(string $serviceId, string $serviceName, array $domains): void;

    /**
     * Registers equivalent domains that share one mailbox.
     */
    public function registerEquivalents(string $canonical, array $equivalents): void;

    /**
     * Registers a provider and optional equivalent map in one call.
     */
    public function register(
        string $serviceId,
        string $serviceName,
        array $domains,
        array $equivalentsMap = []
    ): void;

    /**
     * Returns all known domains.
     *
     * @return string[]
     */
    public function domains(): array;

    /**
     * Returns known services as id => name map.
     *
     * @return array<string, string>
     */
    public function services(): array;
}
