<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Fetches a raw disposable-domain list from an upstream source.
 *
 * Implementations must be swappable without changing the runtime checker.
 */
interface DisposableDomainSourceInterface
{
    /**
     * Returns a stable source identifier for meta/logging.
     */
    public function id(): string;

    /**
     * Fetches domain names from the source.
     *
     * @throws DisposableSourceException When the source cannot be read.
     *
     * @return iterable<string>
     */
    public function fetch(): iterable;
}
