<?php

declare(strict_types=1);

use Firebase\JWT\JWT;

function download_token(int $docId): string
{
    $config = config();
    $payload = ['doc' => $docId, 'exp' => time() + $config['link_ttl']];
    return JWT::encode($payload, $config['link_signing_key'], 'HS256');
}
