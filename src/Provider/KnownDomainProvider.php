<?php

declare(strict_types=1);

namespace EmailAddressKit\Provider;

use EmailAddressKit\Domain\DomainRegistryInterface;

/**
 * View over DomainRegistry that exposes known domains for typo detection.
 */
final class KnownDomainProvider implements KnownDomainProviderInterface
{
    private DomainRegistryInterface $registry;

    
    public function __construct(DomainRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function domains(): array
    {
        return $this->registry->domains();
    }
}
