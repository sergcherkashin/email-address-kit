<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Convenience HTTP source for a GitHub raw domains.txt / domains.json URL.
 *
 * The URL is configurable so the upstream repository can be replaced without code changes.
 */
final class GithubRawDisposableDomainSource implements DisposableDomainSourceInterface
{
    private HttpDisposableDomainSource $inner;

    public function __construct(
        string $rawUrl,
        string $format = 'txt',
        string $id = 'github-raw',
        int $timeoutSeconds = 30
    ) {
        $this->inner = new HttpDisposableDomainSource($rawUrl, $format, $id, $timeoutSeconds);
    }

    /**
     * {@inheritdoc}
     */
    public function id(): string
    {
        return $this->inner->id();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): iterable
    {
        return $this->inner->fetch();
    }
}
