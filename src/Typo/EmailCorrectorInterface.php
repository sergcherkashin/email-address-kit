<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

use EmailAddressKit\Email;

/**
 * Contract for automatic email correction.
 */
interface EmailCorrectorInterface
{
    /**
     * Corrects an email when a high-confidence typo suggestion exists.
     */
    public function correct(Email $email, float $minConfidence = 0.95): Email;
}
