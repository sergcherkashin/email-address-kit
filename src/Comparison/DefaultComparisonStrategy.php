<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

use EmailAddressKit\Email;

/**
 * Default comparison strategy: lowercase + canonical domain from equivalents.
 *
 * Plus-tags are significant by default for every provider.
 * Pass ComparisonOptions::ignorePlusTag() to ignore them.
 * Dots in the local-part are kept significant.
 *
 * Provider domains are not treated as the same mailbox.
 */
class DefaultComparisonStrategy implements EmailComparisonStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function equals(Email $left, Email $right, ?ComparisonOptions $options = null): bool
    {
        return $this->canonical($left, $options) === $this->canonical($right, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function canonical(Email $email, ?ComparisonOptions $options = null): string
    {
        $options ??= ComparisonOptions::default();

        return $this->fingerprint($email, $options);
    }

    /**
     * Builds a comparable fingerprint for an email.
     */
    protected function fingerprint(Email $email, ComparisonOptions $options): string
    {
        return $this->normalizeLocalPart($email, $options) . '@' . $email->domain()->canonical();
    }

    /**
     * Normalizes the local-part according to this strategy and options.
     */
    protected function normalizeLocalPart(Email $email, ComparisonOptions $options): string
    {
        $localPart = $email->address()->normalized();

        if ($options->shouldIgnorePlusTag()) {
            $localPart = $this->stripPlusTag($localPart);
        }

        return $localPart;
    }

    /**
     * Removes plus-tag from a local-part.
     */
    protected function stripPlusTag(string $localPart): string
    {
        $plusPosition = \strpos($localPart, '+');

        if ($plusPosition === false) {
            return $localPart;
        }

        return \substr($localPart, 0, $plusPosition);
    }
}
