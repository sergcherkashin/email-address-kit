<?php

declare(strict_types=1);

namespace EmailAddressKit;

use EmailAddressKit\Address\Address;
use EmailAddressKit\Comparison\ComparisonOptions;
use EmailAddressKit\Comparison\EmailComparisonStrategyInterface;
use EmailAddressKit\Disposable\DisposableDomainChecker;
use EmailAddressKit\Disposable\DisposableDomainCheckerInterface;
use EmailAddressKit\Domain\Domain;
use EmailAddressKit\Exception\InvalidEmailException;
use EmailAddressKit\Service\EmailService;
use EmailAddressKit\Typo\EmailCorrectorInterface;
use EmailAddressKit\Typo\EmailSuggestion;
use EmailAddressKit\Typo\TypoDetectorInterface;
use EmailAddressKit\Validation\EmailValidatorInterface;
use EmailAddressKit\Validation\ValidationOptions;
use EmailAddressKit\Validation\ValidationResult;

/**
 * Immutable facade representing a full email address.
 */
final class Email
{
    private string $original;

    private Address $address;

    private Domain $domain;

    private EmailValidatorInterface $validator;

    private TypoDetectorInterface $typoDetector;

    private EmailCorrectorInterface $corrector;

    private EmailComparisonStrategyInterface $comparisonStrategy;

    private DisposableDomainCheckerInterface $disposableChecker;

    private ?ValidationResult $validationCache = null;

    /** @var EmailSuggestion[]|null */
    private ?array $suggestionsCache = null;

    public function __construct(
        string $original,
        Address $address,
        Domain $domain,
        EmailValidatorInterface $validator,
        TypoDetectorInterface $typoDetector,
        EmailCorrectorInterface $corrector,
        EmailComparisonStrategyInterface $comparisonStrategy,
        ?DisposableDomainCheckerInterface $disposableChecker = null
    ) {
        $this->original = $original;
        $this->address = $address;
        $this->domain = $domain;
        $this->validator = $validator;
        $this->typoDetector = $typoDetector;
        $this->corrector = $corrector;
        $this->comparisonStrategy = $comparisonStrategy;
        $this->disposableChecker = $disposableChecker ?? DisposableDomainChecker::default();
    }

    /**
     * Parses a raw email string using the default library services.
     *
     * @throws InvalidEmailException When input type is not a string (defensive) or empty non-string.
     */
    public static function parse(string $input): self
    {
        return EmailFactory::default()->parse($input);
    }

    /**
     * Returns the local-part object.
     */
    public function address(): Address
    {
        return $this->address;
    }

    /**
     * Returns the domain object.
     */
    public function domain(): Domain
    {
        return $this->domain;
    }

    /**
     * Returns the original raw input string.
     */
    public function original(): string
    {
        return $this->original;
    }

    /**
     * Returns the safely normalized email string (lowercase only).
     *
     * Does not correct typos, remove plus-tags or strip dots.
     */
    public function normalized(): string
    {
        return $this->address->normalized() . '@' . $this->domain->normalized();
    }

    /**
     * String representation for logs, templates and casting.
     *
     * Same as normalized(): safe lowercase, no typo fixes, no domain rewrite.
     * Use original() for the raw input, canonical() for mailbox identity keys.
     */
    public function __toString(): string
    {
        return $this->normalized();
    }

    /**
     * Returns whether the email passes validation.
     *
     * By default only syntax is checked. Pass ValidationOptions::checkDns()
     * to also verify MX/A DNS records for the domain.
     */
    public function isValid(?ValidationOptions $options = null): bool
    {
        return $this->validation($options)->isValid();
    }

    /**
     * Returns whether the domain belongs to a disposable (temporary) mail provider.
     *
     * Independent of isValid() — a disposable address can still be syntactically valid.
     */
    public function isDisposable(): bool
    {
        return $this->disposableChecker->isDisposable($this);
    }

    /**
     * Returns the full validation result.
     *
     * Syntax validation without DNS is cached. DNS-enabled checks are always recomputed.
     */
    public function validation(?ValidationOptions $options = null): ValidationResult
    {
        $options ??= ValidationOptions::default();

        if ($options->shouldCheckDns()) {
            return $this->validator->validate($this, $options);
        }

        if (!$this->validationCache instanceof ValidationResult) {
            $this->validationCache = $this->validator->validate($this, $options);
        }

        return $this->validationCache;
    }

    /**
     * Returns whether at least one suggestion is available.
     */
    public function hasSuggestions(): bool
    {
        return $this->suggestions() !== [];
    }

    /**
     * Returns correction suggestions.
     *
     * @return EmailSuggestion[]
     */
    public function suggestions(): array
    {
        if ($this->suggestionsCache === null) {
            $this->suggestionsCache = $this->typoDetector->suggest($this);
        }

        return $this->suggestionsCache;
    }

    /**
     * Returns a corrected email when a high-confidence domain typo exists.
     *
     * Otherwise returns the same instance.
     */
    public function correct(float $minConfidence = 0.95): self
    {
        return $this->corrector->correct($this, $minConfidence);
    }

    /**
     * Returns the provider service for the domain, if known.
     */
    public function service(): ?EmailService
    {
        return $this->domain->service();
    }

    /**
     * Compares this email with another for mailbox equality.
     *
     * Accepted values for $other: Email instance or raw email string.
     * Plus-tags are significant by default; pass ComparisonOptions::ignorePlusTag()
     * to ignore them for all providers. Pass ComparisonOptions::ignoreGmailDots()
     * to ignore dots for Gmail / googlemail.com only. Multiple flags may be passed
     * as an array.
     *
     * @param mixed                                          $other   Email object or raw email string.
     * @param ComparisonOptions|ComparisonOptions[]|null     $options Comparison flags.
     */
    public function equals($other, $options = null): bool
    {
        if (\is_string($other)) {
            $other = self::parse($other);
        }

        if (!$other instanceof self) {
            return false;
        }

        return $this->comparisonStrategy->equals($this, $other, $options);
    }

    /**
     * Returns a stable mailbox key for storage, indexes and deduplication.
     *
     * Uses the same rules as equals() for the active comparison strategy:
     * lowercase local-part, canonical domain from equivalents, optional flags.
     *
     * Unlike normalized(), this may rewrite the domain (ya.ru → yandex.ru)
     * and, with ComparisonOptions::ignoreGmailDots(), may strip Gmail dots.
     *
     * @param ComparisonOptions|ComparisonOptions[]|null $options Comparison flags.
     */
    public function canonical($options = null): string
    {
        return $this->comparisonStrategy->canonical($this, $options);
    }

    /**
     * Returns entries from $emails that equal this mailbox.
     *
     * Uses the same rules as equals() (case, equivalents, optional ComparisonOptions).
     * Original values and array keys are preserved.
     *
     * @param array<array-key, Email|string>              $emails  Candidate emails (string or Email).
     * @param ComparisonOptions|ComparisonOptions[]|null  $options Comparison flags.
     *
     * @return array<array-key, Email|string>
     */
    public function filterEquals(array $emails, $options = null): array
    {
        $matches = [];

        foreach ($emails as $key => $candidate) {
            if (!$this->equals($candidate, $options)) {
                continue;
            }

            $matches[$key] = $candidate;
        }

        return $matches;
    }

    /**
     * Limits var_dump / print_r output to email identity data.
     *
     * Internal collaborators (validator, registry-backed detectors, etc.) are hidden.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $service = $this->service();

        return [
            'original' => $this->original,
            'address' => $this->address,
            'domain' => $this->domain,
            'normalized' => $this->normalized(),
            'canonical' => $this->canonical(),
            'isValid' => $this->isValid(),
            'isDisposable' => $this->isDisposable(),
            'service' => $service instanceof \EmailAddressKit\Service\EmailService ? $service->id() : null,
        ];
    }
}
