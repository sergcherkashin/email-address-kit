<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

use EmailAddressKit\Email;

/**
 * Checks whether an email/domain belongs to a disposable (temporary) mail provider.
 */
interface DisposableDomainCheckerInterface
{
    /**
     * Returns whether the email uses a disposable domain.
     *
     * @param mixed $email Email object or raw email/domain string.
     */
    public function isDisposable($email): bool;

    /**
     * Returns whether the domain is listed as disposable.
     */
    public function isDisposableDomain(string $domain): bool;
}
