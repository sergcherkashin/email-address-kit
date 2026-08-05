<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

use EmailAddressKit\Email;
use EmailAddressKit\Service\EmailService;

/**
 * Default comparison strategy: lowercase + canonical domain from equivalents.
 *
 * Plus-tags are significant by default for every provider.
 * Pass ComparisonOptions::ignorePlusTag() to ignore them.
 *
 * Dots in the local-part are significant by default (including Gmail).
 * Pass ComparisonOptions::ignoreGmailDots() to ignore dots for Gmail only.
 *
 * Multiple flags may be passed as an array and are merged.
 *
 * Provider domains are not treated as the same mailbox.
 */
final class DefaultComparisonStrategy implements EmailComparisonStrategyInterface
{
    private const GMAIL_CANONICAL = 'gmail.com';

    private const GMAIL_SERVICE_ID = 'gmail';

    /**
     * {@inheritdoc}
     */
    public function equals(Email $left, Email $right, $options = null): bool
    {
        return $this->canonical($left, $options) === $this->canonical($right, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function canonical(Email $email, $options = null): string
    {
        $resolved = ComparisonOptions::resolve($options);

        return $this->normalizeLocalPart($email, $resolved) . '@' . $email->domain()->canonical();
    }

    /**
     * Normalizes the local-part according to comparison options.
     */
    private function normalizeLocalPart(Email $email, ComparisonOptions $options): string
    {
        $localPart = $email->address()->normalized();

        if ($options->shouldIgnorePlusTag()) {
            $localPart = $this->stripPlusTag($localPart);
        }

        if ($options->shouldIgnoreGmailDots() && $this->isGmail($email)) {
            $localPart = \str_replace('.', '', $localPart);
        }

        return $localPart;
    }

    /**
     * Removes plus-tag from a local-part.
     */
    private function stripPlusTag(string $localPart): string
    {
        $plusPosition = \strpos($localPart, '+');

        if ($plusPosition === false) {
            return $localPart;
        }

        return \substr($localPart, 0, $plusPosition);
    }

    /**
     * Checks whether the email belongs to the Gmail mailbox namespace.
     */
    private function isGmail(Email $email): bool
    {
        if ($email->domain()->canonical() === self::GMAIL_CANONICAL) {
            return true;
        }

        $service = $email->domain()->service();

        return $service instanceof EmailService && $service->id() === self::GMAIL_SERVICE_ID;
    }
}
