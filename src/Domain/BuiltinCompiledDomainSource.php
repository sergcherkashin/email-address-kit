<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Loads the compact compiled domain map and reconstructs provider definitions.
 */
final class BuiltinCompiledDomainSource implements DomainDataSourceInterface
{
    private string $domainsFile;

    private string $servicesFile;

    
    public function __construct(string $domainsFile, string $servicesFile)
    {
        $this->domainsFile = $domainsFile;
        $this->servicesFile = $servicesFile;
    }

    /**
     * Creates a source pointing to the package compiled resources.
     */
    public static function default(): self
    {
        $base = \dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'compiled';

        return new self(
            $base . DIRECTORY_SEPARATOR . 'domains.php',
            $base . DIRECTORY_SEPARATOR . 'services.php'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function load(): iterable
    {
        /** @var array<string, array{s: string, c?: string}> $map */
        $map = require $this->domainsFile;
        /** @var array<string, string> $services */
        $services = require $this->servicesFile;

        /** @var array<string, array{id: string, name: string, domains: string[], equivalents: array<string, string[]>}> $providers */
        $providers = [];

        foreach ($map as $domain => $entry) {
            $serviceId = $entry['s'];
            $canonical = $entry['c'] ?? $domain;

            if (!isset($providers[$serviceId])) {
                $providers[$serviceId] = [
                    'id' => $serviceId,
                    'name' => $services[$serviceId] ?? $serviceId,
                    'domains' => [],
                    'equivalents' => [],
                ];
            }

            $providers[$serviceId]['domains'][] = $domain;

            if ($canonical !== $domain) {
                if (!isset($providers[$serviceId]['equivalents'][$canonical])) {
                    $providers[$serviceId]['equivalents'][$canonical] = [];
                }

                $providers[$serviceId]['equivalents'][$canonical][] = $domain;
            }
        }

        foreach ($providers as $provider) {
            if ($provider['equivalents'] === []) {
                unset($provider['equivalents']);
            }

            yield $provider;
        }
    }
}
