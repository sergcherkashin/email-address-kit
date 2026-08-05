<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Parses disposable domain lists from txt or json payloads.
 */
final class DisposableDomainListParser
{
    /**
     * Prevents instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Parses raw list contents into domain strings.
     *
     * @throws DisposableSourceException When the payload cannot be parsed.
     *
     * @return string[]
     */
    public static function parse(string $contents, string $format): array
    {
        $format = \strtolower($format);

        if ($format === 'json') {
            return self::parseJson($contents);
        }

        if ($format === 'txt') {
            return self::parseTxt($contents);
        }

        throw new DisposableSourceException(
            \sprintf('Unsupported disposable list format: %s', $format)
        );
    }

    /**
     * @return string[]
     */
    private static function parseJson(string $contents): array
    {
        $decoded = \json_decode($contents, true);

        if (!\is_array($decoded)) {
            throw new DisposableSourceException('Disposable JSON list is invalid.');
        }

        $domains = [];

        /** @var mixed $item */
        foreach ($decoded as $item) {
            if (\is_string($item)) {
                $domains[] = $item;
                continue;
            }

            if (\is_array($item) && isset($item['domain']) && \is_string($item['domain'])) {
                $domains[] = $item['domain'];
            }
        }

        return $domains;
    }

    /**
     * @return string[]
     */
    private static function parseTxt(string $contents): array
    {
        $lines = \preg_split('/\R/', $contents);

        if ($lines === false) {
            return [];
        }

        $domains = [];

        foreach ($lines as $line) {
            $line = \trim($line);

            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            $domains[] = $line;
        }

        return $domains;
    }
}
