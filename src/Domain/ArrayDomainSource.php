<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Loads provider definitions from an in-memory list (tests / custom use).
 */
final class ArrayDomainSource implements DomainDataSourceInterface
{
    /** @var array<int, array{id: string, name: string, domains: string[], equivalents?: array<string, string[]>}> */
    private array $definitions;

    /**
     * @param array<int, array{id: string, name: string, domains: string[], equivalents?: array<string, string[]>}> $definitions
     *                                                                                                              Provider definitions.
     */
    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function load(): iterable
    {
        foreach ($this->definitions as $definition) {
            yield $definition;
        }
    }
}
