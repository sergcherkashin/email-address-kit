<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

use EmailAddressKit\Email;

/**
 * Contract for email equality comparison strategies.
 */
interface EmailComparisonStrategyInterface
{
    /**
     * Compares two emails for mailbox equality.
     */
    public function equals(Email $left, Email $right, ?ComparisonOptions $options = null): bool;

    /**
     * Returns a stable mailbox key for the email under this strategy.
     *
     * Two emails are equal when their canonical() values are identical
     * (for the same options).
     */
    public function canonical(Email $email, ?ComparisonOptions $options = null): string;
}
