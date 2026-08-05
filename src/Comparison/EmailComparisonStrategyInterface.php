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
     *
     * @param ComparisonOptions|ComparisonOptions[]|null $options Comparison flags.
     */
    public function equals(Email $left, Email $right, $options = null): bool;

    /**
     * Returns a stable mailbox key for the email under this strategy.
     *
     * Two emails are equal when their canonical() values are identical
     * (for the same options).
     *
     * @param ComparisonOptions|ComparisonOptions[]|null $options Comparison flags.
     */
    public function canonical(Email $email, $options = null): string;
}
