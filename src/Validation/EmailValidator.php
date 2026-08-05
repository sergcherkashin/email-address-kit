<?php

declare(strict_types=1);

namespace EmailAddressKit\Validation;

use EmailAddressKit\Email;
use EmailAddressKit\Support\IdnConverter;
use EmailAddressKit\Support\IdnConverterInterface;

/**
 * Performs syntactic validation of an email address.
 *
 * - Local-part: EAI (UTF-8 letters/digits allowed), not converted via Punycode.
 * - Domain: IDN converted to ASCII/Punycode for structural checks (requires intl).
 * - Optional DNS MX/A check via ValidationOptions::checkDns().
 */
final class EmailValidator implements EmailValidatorInterface
{
    private const MAX_ADDRESS_LENGTH = 64;
    private const MAX_DOMAIN_LENGTH = 253;
    private const MAX_EMAIL_LENGTH = 254;

    private DnsLookupInterface $dnsLookup;

    private IdnConverterInterface $idn;

    
    public function __construct(?DnsLookupInterface $dnsLookup = null, ?IdnConverterInterface $idn = null)
    {
        $this->dnsLookup = $dnsLookup ?? new DnsLookup();
        $this->idn = $idn ?? new IdnConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Email $email, ?ValidationOptions $options = null): ValidationResult
    {
        $options ??= ValidationOptions::default();
        $errors = [];
        $original = $email->original();
        $address = $email->address()->value();
        $domain = $email->domain()->value();

        if ($original === '' || \strpos($original, '@') === false) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_FORMAT,
                'Email must contain a local-part and a domain separated by "@".',
                0,
                $original
            );

            return new ValidationResult($errors);
        }

        if (\substr_count($original, '@') !== 1) {
            $atPos = \strpos($original, '@');
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_FORMAT,
                'Email must contain exactly one "@" character.',
                $atPos !== false ? $atPos : null,
                $original
            );
        }

        if (\preg_match('/\s/u', $original) === 1) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_CHARACTER,
                'Email must not contain whitespace characters.',
                null,
                $original
            );
        }

        $asciiDomain = $this->idn->toAscii($domain);

        if ($this->idn->containsNonAscii($domain) && $asciiDomain === null) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_DOMAIN,
                'Domain contains invalid IDN characters or intl extension is unavailable.',
                null,
                $domain
            );
        }

        $domainForChecks = $asciiDomain ?? $domain;
        $emailForLength = $address . '@' . $domainForChecks;

        if (\strlen($emailForLength) > self::MAX_EMAIL_LENGTH) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_FORMAT,
                'Email is longer than 254 characters.',
                null,
                $original
            );
        }

        if ($address === '') {
            $errors[] = new ValidationError(
                ValidationErrorCode::EMPTY_ADDRESS,
                'Local-part must not be empty.',
                0,
                $address
            );
        } elseif (\strlen($address) > self::MAX_ADDRESS_LENGTH) {
            $errors[] = new ValidationError(
                ValidationErrorCode::ADDRESS_TOO_LONG,
                'Local-part must not be longer than 64 octets.',
                0,
                $address
            );
        } elseif (!$this->isValidLocalPart($address)) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_ADDRESS,
                'Local-part contains invalid characters or format.',
                0,
                $address
            );
        }

        if ($domain === '') {
            $atPos = \strpos($original, '@');
            $errors[] = new ValidationError(
                ValidationErrorCode::EMPTY_DOMAIN,
                'Domain must not be empty.',
                $atPos !== false ? $atPos + 1 : null,
                $domain
            );
        } elseif (\strlen($domainForChecks) > self::MAX_DOMAIN_LENGTH) {
            $errors[] = new ValidationError(
                ValidationErrorCode::DOMAIN_TOO_LONG,
                'Domain must not be longer than 253 characters.',
                null,
                $domain
            );
        } elseif ($asciiDomain !== null && !$this->isValidDomain($domainForChecks)) {
            $errors[] = new ValidationError(
                ValidationErrorCode::INVALID_DOMAIN,
                'Domain has invalid structure or characters.',
                null,
                $domain
            );
        }

        if ($errors === [] && $options->shouldCheckDns()) {
            $dnsDomain = $email->domain()->ascii();

            if (!$this->dnsLookup->hasMxOrARecord($dnsDomain)) {
                $errors[] = new ValidationError(
                    ValidationErrorCode::DNS_CHECK_FAILED,
                    'Domain has no MX or A DNS record.',
                    null,
                    $dnsDomain
                );
            }
        }

        return new ValidationResult($errors);
    }

    /**
     * Validates local-part syntax including UTF-8 EAI characters.
     */
    private function isValidLocalPart(string $localPart): bool
    {
        if ($localPart === '' || $localPart[0] === '.' || \substr($localPart, -1) === '.') {
            return false;
        }

        if (\strpos($localPart, '..') !== false) {
            return false;
        }

        return \preg_match('/^[\p{L}\p{N}!#$%&\'*+\/=?^_`{|}~.-]+$/u', $localPart) === 1;
    }

    /**
     * Validates ASCII domain syntax (including Punycode labels).
     */
    private function isValidDomain(string $domain): bool
    {
        if ($domain === '' || $domain[0] === '.' || \substr($domain, -1) === '.') {
            return false;
        }

        if (\strpos($domain, '..') !== false) {
            return false;
        }

        $labels = \explode('.', $domain);

        if (\count($labels) < 2) {
            return false;
        }

        foreach ($labels as $label) {
            if ($label === '' || \strlen($label) > 63) {
                return false;
            }

            if (\preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $label) !== 1) {
                return false;
            }
        }

        return true;
    }
}
