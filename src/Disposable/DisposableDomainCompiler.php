<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Compiles disposable-domain sources into a runtime PHP hash map.
 */
final class DisposableDomainCompiler
{
    private string $outputDirectory;

    
    public function __construct(string $outputDirectory)
    {
        $this->outputDirectory = \rtrim($outputDirectory, '/\\');
    }

    /**
     * Compiles domains from primary source, with optional fallback and allowlist.
     *
     *
     * @throws DisposableSourceException When no source can be loaded.
     *
     * @return array{count: int, source: string, path: string}
     */
    public function compile(
        DisposableDomainSourceInterface $primary,
        ?DisposableDomainSourceInterface $fallback = null,
        array $allowlist = []
    ): array {
        $usedSource = $primary;
        $domains = [];

        try {
            $domains = $this->collect($primary);
        } catch (DisposableSourceException $primaryException) {
            if (!$fallback instanceof \EmailAddressKit\Disposable\DisposableDomainSourceInterface) {
                throw $primaryException;
            }

            $usedSource = $fallback;
            $domains = $this->collect($fallback);
        }

        /** @var array<string, true> $allowMap */
        $allowMap = [];
        foreach ($allowlist as $domain) {
            if (!\is_string($domain) || $domain === '') {
                continue;
            }
            $allowMap[\strtolower(\trim($domain))] = true;
        }

        /** @var array<string, true> $map */
        $map = [];
        foreach ($domains as $domain) {
            $normalized = \strtolower(\trim($domain));
            $normalized = \rtrim($normalized, '.');

            if ($normalized === '' || isset($allowMap[$normalized])) {
                continue;
            }

            $map[$normalized] = true;
        }

        \ksort($map);

        if (!\is_dir($this->outputDirectory)
            && !\mkdir($this->outputDirectory, 0777, true)
            && !\is_dir($this->outputDirectory)
        ) {
            throw new DisposableSourceException(
                \sprintf('Unable to create compiled directory: %s', $this->outputDirectory)
            );
        }

        $mapPath = $this->outputDirectory . DIRECTORY_SEPARATOR . 'disposable.php';
        $metaPath = $this->outputDirectory . DIRECTORY_SEPARATOR . 'disposable-meta.php';

        $export = \var_export($map, true);
        $mapPhp = <<<PHP
<?php

declare(strict_types=1);

/**
 * Auto-generated disposable domain index.
 *
 * Do not edit manually. Run: php bin/update-disposable
 *
 * @return array<string, true>
 */
return {$export};

PHP;

        $count = \count($map);
        $generatedAt = \gmdate('c');
        $sourceId = $usedSource->id();
        $metaPhp = <<<PHP
<?php

declare(strict_types=1);

/**
 * Auto-generated disposable dataset metadata.
 *
 * @return array{version: int, count: int, source: string, generated_at: string}
 */
return [
    'version' => 1,
    'count' => {$count},
    'source' => '{$sourceId}',
    'generated_at' => '{$generatedAt}',
];

PHP;

        if (\file_put_contents($mapPath, $mapPhp) === false
            || \file_put_contents($metaPath, $metaPhp) === false
        ) {
            throw new DisposableSourceException('Unable to write compiled disposable domain files.');
        }

        return [
            'count' => $count,
            'source' => $sourceId,
            'path' => $mapPath,
        ];
    }

    /**
     * @return string[]
     */
    private function collect(DisposableDomainSourceInterface $source): array
    {
        $domains = [];

        foreach ($source->fetch() as $domain) {
            if ($domain !== '') {
                $domains[] = $domain;
            }
        }

        if ($domains === []) {
            throw new DisposableSourceException(
                \sprintf('Disposable source "%s" returned an empty list.', $source->id())
            );
        }

        return $domains;
    }
}
