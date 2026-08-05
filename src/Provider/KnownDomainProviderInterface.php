<?php

declare(strict_types=1);

namespace EmailAddressKit\Provider;

/**
 * Provides a flat list of known domains for typo detection.
 */
interface KnownDomainProviderInterface
{
    /**
     * Returns all known domains.
     *
     * @return string[]
     */
    public function domains(): array;
}
