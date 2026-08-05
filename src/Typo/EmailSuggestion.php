<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

use EmailAddressKit\Email;

/**
 * A candidate correction for an email address.
 */
final class EmailSuggestion
{
    private Email $email;

    private float $score;

    private string $reason;

    
    public function __construct(Email $email, float $score, string $reason)
    {
        $this->email = $email;
        $this->score = $score;
        $this->reason = $reason;
    }

    /**
     * Returns the suggested email.
     */
    public function email(): Email
    {
        return $this->email;
    }

    /**
     * Returns the confidence score.
     */
    public function score(): float
    {
        return $this->score;
    }

    /**
     * Returns the suggestion reason code.
     */
    public function reason(): string
    {
        return $this->reason;
    }
}
