<?php

declare(strict_types=1);

namespace EmailAddressKit\Support;

/**
 * IDN converter based on PHP intl idn_to_ascii().
 */
final class IdnConverter implements IdnConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function containsNonAscii(string $value): bool
    {
        return \preg_match('/[^\x00-\x7F]/', $value) === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function toLower(string $value): string
    {
        if (\function_exists('mb_strtolower')) {
            return \mb_strtolower($value, 'UTF-8');
        }

        return \strtolower($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toAscii(string $value): ?string
    {
        if ($value === '') {
            return '';
        }

        $lower = $this->toLower($value);

        if (!$this->containsNonAscii($lower)) {
            return $lower;
        }

        if (!\function_exists('idn_to_ascii')) {
            return null;
        }

        $ascii = $this->convertWithIntl($lower);

        if ($ascii === false || $ascii === '') {
            return null;
        }

        return \strtolower($ascii);
    }

    /**
     * Calls idn_to_ascii with the best available signature for the runtime.
     *
     *
     * @return string|false
     */
    private function convertWithIntl(string $value)
    {
        if (\defined('INTL_IDNA_VARIANT_UTS46')) {
            /** @var int $flags */
            $flags = \defined('IDNA_NONTRANSITIONAL_TO_ASCII') ? \constant('IDNA_NONTRANSITIONAL_TO_ASCII') : 0;
            /** @var int $variant */
            $variant = \constant('INTL_IDNA_VARIANT_UTS46');

            return \idn_to_ascii($value, $flags, $variant);
        }

        return \idn_to_ascii($value);
    }
}
