<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

use InvalidArgumentException;

/**
 * Flags that control email equality comparison.
 *
 * Pass a single flag or an array of flags into equals() / canonical() / filterEquals().
 * Multiple flags are merged (OR): all listed rules apply.
 *
 * By default plus-tags and dots stay significant.
 */
final class ComparisonOptions
{
    private bool $ignorePlusTag;

    private bool $ignoreGmailDots;

    private function __construct(bool $ignorePlusTag, bool $ignoreGmailDots)
    {
        $this->ignorePlusTag = $ignorePlusTag;
        $this->ignoreGmailDots = $ignoreGmailDots;
    }

    /**
     * Default options: plus-tags and dots stay significant.
     */
    public static function default(): self
    {
        return new self(false, false);
    }

    /**
     * Ignore plus-tags for all providers.
     */
    public static function ignorePlusTag(): self
    {
        return new self(true, false);
    }

    /**
     * Ignore dots in the local-part for Gmail / googlemail.com only.
     */
    public static function ignoreGmailDots(): self
    {
        return new self(false, true);
    }

    /**
     * Normalizes a single flag, an array of flags, or null into one options object.
     *
     * @param mixed $options ComparisonOptions, ComparisonOptions[], or null.
     *
     * @throws InvalidArgumentException When the value cannot be resolved.
     */
    public static function resolve($options): self
    {
        if ($options === null) {
            return self::default();
        }

        if ($options instanceof self) {
            return $options;
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException(
                'Comparison options must be ComparisonOptions, ComparisonOptions[], or null.'
            );
        }

        $ignorePlusTag = false;
        $ignoreGmailDots = false;

        foreach ($options as $item) {
            if (!$item instanceof self) {
                throw new InvalidArgumentException(
                    'Each comparison option must be an instance of ComparisonOptions.'
                );
            }

            $ignorePlusTag = $ignorePlusTag || $item->ignorePlusTag;
            $ignoreGmailDots = $ignoreGmailDots || $item->ignoreGmailDots;
        }

        return new self($ignorePlusTag, $ignoreGmailDots);
    }

    /**
     * Returns whether plus-tags must be ignored.
     */
    public function shouldIgnorePlusTag(): bool
    {
        return $this->ignorePlusTag;
    }

    /**
     * Returns whether Gmail local-part dots must be ignored.
     */
    public function shouldIgnoreGmailDots(): bool
    {
        return $this->ignoreGmailDots;
    }
}
