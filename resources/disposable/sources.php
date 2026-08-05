<?php

declare(strict_types=1);

/**
 * Disposable domain list sources.
 *
 * Swap primary/fallback URLs when an upstream repository is abandoned.
 * Runtime checker only reads resources/compiled/disposable.php.
 *
 * @return array{
 *     primary: array<string, mixed>,
 *     fallback?: array<string, mixed>|null,
 *     allowlist: string
 * }
 */
return [
    'primary' => [
        'type' => 'github-raw',
        'id' => 'groundcat',
        'format' => 'txt',
        'url' => 'https://raw.githubusercontent.com/groundcat/disposable-email-domain-list/master/domains.txt',
        'timeout' => 30,
    ],
    // Optional second source if primary fails. Set to null to disable.
    'fallback' => [
        'type' => 'local',
        'id' => 'bundled-seed',
        'format' => 'txt',
        'path' => __DIR__ . DIRECTORY_SEPARATOR . 'seed.txt',
    ],
    'allowlist' => __DIR__ . DIRECTORY_SEPARATOR . 'allowlist.txt',
];
