<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Combines multiple domain data sources.
 *
 * Later sources can add or override providers via sequential registration.
 */
final class CompositeDomainSource implements DomainDataSourceInterface
{
    /** @var DomainDataSourceInterface[] */
    private array $sources;

    /**
     * @param DomainDataSourceInterface[] $sources Sources in load order.
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function load(): iterable
    {
        foreach ($this->sources as $source) {
            foreach ($source->load() as $definition) {
                yield $definition;
            }
        }
    }
}
