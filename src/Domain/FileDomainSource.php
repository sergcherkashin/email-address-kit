<?php

declare(strict_types=1);

namespace EmailAddressKit\Domain;

/**
 * Loads provider definitions from PHP files in a directory.
 */
final class FileDomainSource implements DomainDataSourceInterface
{
    private string $directory;

    
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     *
     * @return iterable<int, array{id: string, name: string, domains: string[], equivalents?: array<string, string[]>}>
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function load(): iterable
    {
        $pattern = \rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . '*.php';
        $files = \glob($pattern);

        if ($files === false) {
            return;
        }

        \sort($files);

        foreach ($files as $file) {
            /** @var mixed $definition */
            $definition = require $file;

            if (!\is_array($definition)) {
                continue;
            }

            if (!isset($definition['id'], $definition['name'], $definition['domains'])
                || !\is_string($definition['id'])
                || !\is_string($definition['name'])
                || !\is_array($definition['domains'])
            ) {
                continue;
            }

            /** @var array{id: string, name: string, domains: string[], equivalents?: array<string, string[]>} $definition */
            yield $definition;
        }
    }
}
