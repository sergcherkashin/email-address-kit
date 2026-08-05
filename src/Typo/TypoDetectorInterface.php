<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

use EmailAddressKit\Email;

/**
 * Contract for domain typo detection.
 */
interface TypoDetectorInterface
{
    /**
     * Suggests corrections for an email.
     *
     *
     * @return EmailSuggestion[]
     */
    public function suggest(Email $email): array;
}
