<?php

declare(strict_types=1);

return [
    'db_path' => __DIR__ . '/../storage/docvault.db',
    'storage_dir' => __DIR__ . '/../storage/files',
    'template_dir' => __DIR__ . '/../templates',
    'max_import_bytes' => 5 * 1024 * 1024,
    'link_ttl' => 3600,
    // TESTBED SEC-01
    'link_signing_key' => '4Jajd28yBrXMICNHdTxvR0eBikRR5nA8hx06PnUv',
];
