<?php

declare(strict_types=1);

namespace EmailAddressKit\Typo;

use EmailAddressKit\Domain\DomainInfo;
use EmailAddressKit\Domain\DomainRegistryInterface;
use EmailAddressKit\Email;
use EmailAddressKit\Provider\KnownDomainProviderInterface;

/**
 * Detects domain typos and equivalent-domain rewrites.
 */
final class TypoDetector implements TypoDetectorInterface
{
    private const MIN_DISPLAY_SCORE = 0.80;

    /**
     * Soft preference for globally common mailbox domains when scores are close.
     *
     * @var array<string, float>
     */
    private const POPULARITY_BOOST = [
        'gmail.com' => 0.03,
        'googlemail.com' => 0.025,
        'yahoo.com' => 0.02,
        'outlook.com' => 0.02,
        'hotmail.com' => 0.02,
        'live.com' => 0.015,
        'icloud.com' => 0.02,
        'me.com' => 0.015,
        'mail.ru' => 0.02,
        'yandex.ru' => 0.02,
        'ya.ru' => 0.015,
        'proton.me' => 0.015,
        'protonmail.com' => 0.015,
        'aol.com' => 0.01,
        'zoho.com' => 0.01,
    ];

    private KnownDomainProviderInterface $knownDomains;

    private DomainRegistryInterface $registry;

    /** @var callable */
    private $emailFactory;

    /**
     * @param callable                     $emailFactory Factory that builds Email from a string:
     *                                                   function (string $value): Email
     */
    public function __construct(
        KnownDomainProviderInterface $knownDomains,
        DomainRegistryInterface $registry,
        callable $emailFactory
    ) {
        $this->knownDomains = $knownDomains;
        $this->registry = $registry;
        $this->emailFactory = $emailFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function suggest(Email $email): array
    {
        $domain = $email->domain()->normalized();
        $suggestions = [];

        $info = $this->registry->find($domain);

        if ($info instanceof DomainInfo) {
            $canonical = $info->canonical();

            if ($canonical !== $domain) {
                $suggestions[] = $this->makeSuggestion(
                    $email,
                    $canonical,
                    1.0,
                    SuggestionReason::DOMAIN_EQUIVALENT
                );
            }

            return $this->filterAndSort($suggestions);
        }

        $best = $this->findBestTypoCandidate($domain);

        if ($best !== null) {
            $suggestions[] = $this->makeSuggestion(
                $email,
                $best['domain'],
                $best['score'],
                SuggestionReason::DOMAIN_TYPO
            );
        }

        return $this->filterAndSort($suggestions);
    }

    /**
     * Finds the best typo candidate among known domains.
     *
     *
     * @return array{domain: string, score: float}|null
     */
    private function findBestTypoCandidate(string $domain): ?array
    {
        $bestDomain = null;
        $bestScore = 0.0;

        foreach ($this->knownDomains->domains() as $candidate) {
            $score = $this->scoreCandidate($domain, $candidate);

            if ($bestDomain === null
                || $score > $bestScore
                || ($score === $bestScore && $this->popularityBoost($candidate) > $this->popularityBoost($bestDomain))
            ) {
                $bestScore = $score;
                $bestDomain = $candidate;
            }
        }

        if ($bestDomain === null || $bestScore < self::MIN_DISPLAY_SCORE) {
            return null;
        }

        return [
            'domain' => $bestDomain,
            'score' => $bestScore,
        ];
    }

    /**
     * Calculates similarity score between an input domain and a known candidate.
     */
    private function scoreCandidate(string $input, string $candidate): float
    {
        if ($input === $candidate) {
            return 1.0;
        }

        $distance = \levenshtein($input, $candidate);
        $maxLen = \max(\strlen($input), \strlen($candidate));

        if ($maxLen === 0) {
            return 0.0;
        }

        $score = 1.0 - ($distance / $maxLen);

        if ($this->isAdjacentSwap($input, $candidate)) {
            $score = \max($score, 0.97);
        }

        if ($distance === 1) {
            $score = \max($score, 0.95);
        }

        if ($distance === 2 && $maxLen >= 6) {
            $score = \max($score, 0.90);
        }

        $inputTld = $this->tld($input);
        $candidateTld = $this->tld($candidate);
        $inputName = $this->nameWithoutTld($input);
        $candidateName = $this->nameWithoutTld($candidate);

        // Common TLD mistake: gmail.ru → gmail.com (exact SLD, different TLD).
        // Must outrank near-misses like gmail.ru → mail.ru (Levenshtein = 1).
        if ($inputName !== '' && $inputName === $candidateName && $inputTld !== $candidateTld) {
            $score = \max($score, 0.99);
        } elseif ($inputTld !== '' && $inputTld === $candidateTld && $distance <= 2) {
            $score = \min(1.0, $score + 0.02);
        }

        return \min(1.0, $score + $this->popularityBoost($candidate));
    }

    /**
     * Returns a small score boost for globally popular mailbox domains.
     */
    private function popularityBoost(string $domain): float
    {
        return self::POPULARITY_BOOST[$domain] ?? 0.0;
    }

    /**
     * Checks whether two strings differ by a single adjacent character swap.
     */
    private function isAdjacentSwap(string $left, string $right): bool
    {
        if (\strlen($left) !== \strlen($right)) {
            return false;
        }

        $length = \strlen($left);
        $diffPositions = [];

        for ($i = 0; $i < $length; $i++) {
            if ($left[$i] !== $right[$i]) {
                $diffPositions[] = $i;
            }
        }

        if (\count($diffPositions) !== 2) {
            return false;
        }

        $a = $diffPositions[0];
        $b = $diffPositions[1];

        return ($b === $a + 1)
            && $left[$a] === $right[$b]
            && $left[$b] === $right[$a];
    }

    /**
     * Returns the TLD label of a domain.
     */
    private function tld(string $domain): string
    {
        $parts = \explode('.', $domain);

        return $parts[\count($parts) - 1];
    }

    /**
     * Returns domain without the last label.
     */
    private function nameWithoutTld(string $domain): string
    {
        $parts = \explode('.', $domain);

        if (\count($parts) < 2) {
            return $domain;
        }

        \array_pop($parts);

        return \implode('.', $parts);
    }

    /**
     * Builds a suggestion email with a replaced domain.
     */
    private function makeSuggestion(Email $email, string $newDomain, float $score, string $reason): EmailSuggestion
    {
        $value = $email->address()->value() . '@' . $newDomain;
        $factory = $this->emailFactory;
        /** @var Email $suggested */
        $suggested = $factory($value);

        return new EmailSuggestion($suggested, $score, $reason);
    }

    /**
     * Filters low-confidence suggestions and sorts by score descending.
     *
     * @param EmailSuggestion[] $suggestions Candidate suggestions.
     *
     * @return EmailSuggestion[]
     */
    private function filterAndSort(array $suggestions): array
    {
        $filtered = \array_values(\array_filter(
            $suggestions,
            static function (EmailSuggestion $suggestion): bool {
                return $suggestion->score() >= self::MIN_DISPLAY_SCORE;
            }
        ));

        \usort(
            $filtered,
            static function (EmailSuggestion $left, EmailSuggestion $right): int {
                return $right->score() <=> $left->score();
            }
        );

        return $filtered;
    }
}
