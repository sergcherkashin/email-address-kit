<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

use EmailAddressKit\Email;

/**
 * Extends default comparison with Gmail mailbox rules for dots.
 *
 * Inherits plus-tag handling and canonical-domain comparison from
 * DefaultComparisonStrategy.
 *
 * Gmail-only (`gmail.com` / `googlemail.com` via canonical):
 * - dots in the local-part are ignored (`ser.g` = `serg`).
 *
 * Non-Gmail addresses keep dots significant.
 *
 * Normalization via Email::normalized() is not affected by this strategy.
 */
final class GmailComparisonStrategy extends DefaultComparisonStrategy
{
    private const GMAIL_CANONICAL = 'gmail.com';
    private const GMAIL_SERVICE_ID = 'gmail';

    /**
     * {@inheritdoc}
     *
     * Additionally removes dots from Gmail local-parts.
     */
    protected function normalizeLocalPart(Email $email, ComparisonOptions $options): string
    {
        $localPart = parent::normalizeLocalPart($email, $options);

        if ($this->isGmail($email)) {
            $localPart = \str_replace('.', '', $localPart);
        }

        return $localPart;
    }

    /**
     * Checks whether the email belongs to Gmail mailbox namespace.
     */
    private function isGmail(Email $email): bool
    {
        if ($email->domain()->canonical() === self::GMAIL_CANONICAL) {
            return true;
        }

        $service = $email->domain()->service();

        return $service instanceof \EmailAddressKit\Service\EmailService && $service->id() === self::GMAIL_SERVICE_ID;
    }
}
