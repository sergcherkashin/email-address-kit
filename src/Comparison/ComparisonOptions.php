<?php

declare(strict_types=1);

namespace EmailAddressKit\Comparison;

/**
 * Options that control email equality comparison.
 *
 * By default, plus-tags are significant for all providers:
 * `john+news@example.com` is not equal to `john@example.com`.
 *
 * Pass ignorePlusTag() when plus-tags must be ignored during comparison.
 */
final class ComparisonOptions
{
    private bool $ignorePlusTag;

    private function __construct(bool $ignorePlusTag)
    {
        $this->ignorePlusTag = $ignorePlusTag;
    }

    /**
     * Default options: keep plus-tags significant.
     */
    public static function default(): self
    {
        return new self(false);
    }

    /**
     * Options that ignore plus-tags for all providers.
     */
    public static function ignorePlusTag(): self
    {
        return new self(true);
    }

    /**
     * Returns whether plus-tags must be ignored.
     */
    public function shouldIgnorePlusTag(): bool
    {
        return $this->ignorePlusTag;
    }
}
