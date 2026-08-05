<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Loads disposable domains from a local text or JSON file.
 */
final class LocalFileDisposableDomainSource implements DisposableDomainSourceInterface
{
    private string $id;

    private string $path;

    private string $format;

    public function __construct(string $path, string $format = 'txt', string $id = 'local')
    {
        $this->path = $path;
        $this->format = \strtolower($format);
        $this->id = $id;
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
        if (!\is_file($this->path) || !\is_readable($this->path)) {
            throw new DisposableSourceException(
                \sprintf('Disposable domain file is not readable: %s', $this->path)
            );
        }

        $contents = \file_get_contents($this->path);

        if ($contents === false) {
            throw new DisposableSourceException(
                \sprintf('Unable to read disposable domain file: %s', $this->path)
            );
        }

        return DisposableDomainListParser::parse($contents, $this->format);
    }
}
