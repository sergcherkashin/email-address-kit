<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

use EmailAddressKit\Exception\EmailException;
use EmailAddressKit\Service\EmailService;

/**
 * In-memory registry of providers, domains and mailbox equivalents.
 */
final class DomainRegistry implements DomainRegistryInterface
{
    /** @var array<string, DomainInfo> */
    private array $index = [];

    /** @var array<string, EmailService> */
    private array $services = [];

    /** @var array<string, string[]> */
    private array $providerDomainMap = [];

    /** @var array<string, string> */
    private array $canonicalMap = [];

    /** @var array<string, string[]> */
    private array $equivalentGroups = [];

    /**
     * Creates a registry preloaded from a data source.
     */
    public static function fromDataSource(DomainDataSourceInterface $source): self
    {
        $registry = new self();

        foreach ($source->load() as $definition) {
            $equivalents = [];

            if (isset($definition['equivalents'])) {
                /** @var array<string, string[]> $equivalents */
                $equivalents = $definition['equivalents'];
            }

            $registry->register(
                $definition['id'],
                $definition['name'],
                $definition['domains'],
                $equivalents
            );
        }

        return $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $domain): ?DomainInfo
    {
        $normalized = \strtolower($domain);

        return $this->index[$normalized] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function canonical(string $domain): string
    {
        $normalized = \strtolower($domain);

        return $this->canonicalMap[$normalized] ?? $normalized;
    }

    /**
     * Registers a provider and its domains.
     *
     * Each domain becomes its own canonical unless equivalents are registered later.
     */
    public function registerProvider(string $serviceId, string $serviceName, array $domains): void
    {
        $service = new EmailService($serviceId, $serviceName);
        $this->services[$serviceId] = $service;

        $normalizedDomains = [];
        foreach ($domains as $domain) {
            if (!\is_string($domain) || $domain === '') {
                throw new EmailException('Provider domain must be a non-empty string.');
            }

            $normalizedDomains[] = \strtolower($domain);
        }

        $normalizedDomains = \array_values(\array_unique($normalizedDomains));
        $this->providerDomainMap[$serviceId] = $normalizedDomains;

        foreach ($normalizedDomains as $domain) {
            if (!isset($this->canonicalMap[$domain])) {
                $this->canonicalMap[$domain] = $domain;
            }

            if (!isset($this->equivalentGroups[$domain])) {
                $this->equivalentGroups[$domain] = [$domain];
            }

            $this->rebuildDomainInfo($domain);
        }

        foreach ($normalizedDomains as $domain) {
            $this->rebuildDomainInfo($domain);
        }
    }

    /**
     * Registers equivalent domains that share one mailbox.
     */
    public function registerEquivalents(string $canonical, array $equivalents): void
    {
        $canonicalNormalized = \strtolower($canonical);

        if (!isset($this->index[$canonicalNormalized])) {
            throw new EmailException(
                \sprintf('Canonical domain "%s" must be registered as a provider domain first.', $canonical)
            );
        }

        $group = [$canonicalNormalized];

        foreach ($equivalents as $equivalent) {
            if (!\is_string($equivalent) || $equivalent === '') {
                throw new EmailException('Equivalent domain must be a non-empty string.');
            }

            $normalized = \strtolower($equivalent);

            if ($normalized === $canonicalNormalized) {
                continue;
            }

            if (!isset($this->index[$normalized])) {
                throw new EmailException(
                    \sprintf('Equivalent domain "%s" must be registered as a provider domain first.', $equivalent)
                );
            }

            if (
                isset($this->canonicalMap[$normalized])
                && $this->canonicalMap[$normalized] !== $normalized
                && $this->canonicalMap[$normalized] !== $canonicalNormalized
            ) {
                throw new EmailException(
                    \sprintf('Domain "%s" already belongs to another equivalent group.', $equivalent)
                );
            }

            $group[] = $normalized;
        }

        $group = \array_values(\array_unique($group));

        foreach ($group as $domain) {
            $this->canonicalMap[$domain] = $canonicalNormalized;
            $this->equivalentGroups[$domain] = $group;
        }

        foreach ($group as $domain) {
            $this->rebuildDomainInfo($domain);
        }
    }

    /**
     * Registers a provider and optional equivalent map in one call.
     */
    public function register(
        string $serviceId,
        string $serviceName,
        array $domains,
        array $equivalentsMap = []
    ): void {
        $this->registerProvider($serviceId, $serviceName, $domains);

        foreach ($equivalentsMap as $canonical => $equivalents) {
            if (!\is_string($canonical)) {
                throw new EmailException('Equivalent map keys must be strings.');
            }

            if (!\is_array($equivalents)) {
                throw new EmailException('Equivalent map values must be arrays of domains.');
            }

            $this->registerEquivalents($canonical, $equivalents);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function domains(): array
    {
        return \array_keys($this->index);
    }

    /**
     * {@inheritdoc}
     */
    public function services(): array
    {
        $map = [];

        foreach ($this->services as $id => $service) {
            $map[$id] = $service->name();
        }

        \ksort($map);

        return $map;
    }

    /**
     * Rebuilds DomainInfo for a domain from internal maps.
     */
    private function rebuildDomainInfo(string $domain): void
    {
        $service = null;
        $providerDomains = [];

        foreach ($this->providerDomainMap as $serviceId => $domains) {
            if (\in_array($domain, $domains, true)) {
                $service = $this->services[$serviceId];
                $providerDomains = $domains;
                break;
            }
        }

        $canonical = $this->canonicalMap[$domain] ?? $domain;
        $equivalents = $this->equivalentGroups[$canonical] ?? [$domain];

        if (!\in_array($domain, $equivalents, true)) {
            $equivalents = \array_values(\array_unique(\array_merge($equivalents, [$domain])));
        }

        $this->index[$domain] = new DomainInfo(
            $domain,
            $canonical,
            $service,
            $equivalents,
            $providerDomains
        );
    }
}
