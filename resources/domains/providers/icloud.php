<?php

declare(strict_types=1);

return [
    'id' => 'icloud',
    'name' => 'iCloud',
    'domains' => [
        'icloud.com',
        'me.com',
        'mac.com',
    ],
    'equivalents' => [
        // Same Apple ID mailbox aliases
        'icloud.com' => [
            'me.com',
            'mac.com',
        ],
    ],
];
