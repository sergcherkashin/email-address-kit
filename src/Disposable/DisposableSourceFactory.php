<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Builds disposable-domain sources from a configuration array.
 */
final class DisposableSourceFactory
{
    /**
     * Prevents instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Creates a source from a config entry.
     *
     * Supported types: http, github-raw, local, composite.
     *
     * @param array<string, mixed> $config Source configuration.
     *
     * @throws DisposableSourceException When configuration is invalid.
     */
    public static function fromConfig(array $config): DisposableDomainSourceInterface
    {
        $type = isset($config['type']) ? \strtolower((string) $config['type']) : '';

        if ($type === 'composite') {
            if (!isset($config['sources']) || !\is_array($config['sources'])) {
                throw new DisposableSourceException('Composite source requires a sources array.');
            }

            $children = [];
            foreach ($config['sources'] as $child) {
                if (!\is_array($child)) {
                    throw new DisposableSourceException('Composite child source must be an array.');
                }
                /** @var array<string, mixed> $childConfig */
                $childConfig = $child;
                $children[] = self::fromConfig($childConfig);
            }

            return new CompositeDisposableDomainSource(
                $children,
                isset($config['id']) ? (string) $config['id'] : 'composite'
            );
        }

        $id = isset($config['id']) ? (string) $config['id'] : $type;
        $format = isset($config['format']) ? (string) $config['format'] : 'txt';
        $timeout = isset($config['timeout']) ? (int) $config['timeout'] : 30;

        if ($type === 'local') {
            if (!isset($config['path']) || !\is_string($config['path'])) {
                throw new DisposableSourceException('Local source requires a path.');
            }

            return new LocalFileDisposableDomainSource($config['path'], $format, $id);
        }

        if ($type === 'http' || $type === 'github-raw') {
            if (!isset($config['url']) || !\is_string($config['url'])) {
                throw new DisposableSourceException(\sprintf('%s source requires a url.', $type));
            }

            if ($type === 'github-raw') {
                return new GithubRawDisposableDomainSource($config['url'], $format, $id, $timeout);
            }

            return new HttpDisposableDomainSource($config['url'], $format, $id, $timeout);
        }

        throw new DisposableSourceException(\sprintf('Unknown disposable source type: %s', $type));
    }
}
