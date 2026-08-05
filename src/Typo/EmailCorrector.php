<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

use EmailAddressKit\Email;

/**
 * Applies high-confidence domain typo corrections.
 */
final class EmailCorrector implements EmailCorrectorInterface
{
    private TypoDetectorInterface $detector;

    
    public function __construct(TypoDetectorInterface $detector)
    {
        $this->detector = $detector;
    }

    /**
     * {@inheritdoc}
     */
    public function correct(Email $email, float $minConfidence = 0.95): Email
    {
        foreach ($this->detector->suggest($email) as $suggestion) {
            if ($suggestion->reason() !== SuggestionReason::DOMAIN_TYPO) {
                continue;
            }

            if ($suggestion->score() >= $minConfidence) {
                return $suggestion->email();
            }
        }

        return $email;
    }
}
