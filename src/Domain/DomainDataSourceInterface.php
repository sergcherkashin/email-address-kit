<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Loads provider definitions for DomainRegistry.
 *
 * Each item must contain:
 * - id: string
 * - name: string
 * - domains: string[]
 * - equivalents?: array<string, string[]>
 */
interface DomainDataSourceInterface
{
    /**
     * Loads provider definitions.
     *
     * @return iterable<array{
     *     id: string,
     *     name: string,
     *     domains: string[],
     *     equivalents?: array<string, string[]>
     * }>
     */
    public function load(): iterable;
}
