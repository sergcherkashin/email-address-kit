<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Loads disposable domains over HTTP(S).
 */
final class HttpDisposableDomainSource implements DisposableDomainSourceInterface
{
    private string $id;

    private string $url;

    private string $format;

    private int $timeoutSeconds;

    public function __construct(
        string $url,
        string $format = 'txt',
        string $id = 'http',
        int $timeoutSeconds = 30
    ) {
        $this->url = $url;
        $this->format = \strtolower($format);
        $this->id = $id;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * {@inheritdoc}
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): iterable
    {
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: EmailAddressKit-DisposableUpdater\r\nAccept: */*\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $contents = @\file_get_contents($this->url, false, $context);

        if ($contents === false || $contents === '') {
            throw new DisposableSourceException(
                \sprintf('Unable to download disposable domain list from: %s', $this->url)
            );
        }

        return DisposableDomainListParser::parse($contents, $this->format);
    }
}
