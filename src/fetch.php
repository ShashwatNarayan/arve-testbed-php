<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

function import_from_url(): void
{
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    $url = is_array($input) ? ($input['url'] ?? '') : '';
    if (!is_string($url) || !preg_match('#^https?://#i', $url)) {
        error_response('url is required', 400);
        return;
    }
    $client = new Client(['timeout' => 10, 'allow_redirects' => false]);
    try {
        $response = $client->get($url);
    } catch (GuzzleException $e) {
        error_response('fetch failed', 502);
        return;
    }
    $body = (string) $response->getBody();
    if (strlen($body) > config()['max_import_bytes']) {
        error_response('document too large', 413);
        return;
    }
    $stored = bin2hex(random_bytes(16)) . '.txt';
    $target = config()['storage_dir'] . '/' . $stored;
    file_put_contents($target, $body);
    $title = is_string($input['title'] ?? null) ? $input['title'] : $url;
    $id = insert_document(db(), $title, $stored, hash('sha256', $body));
    json_response(['id' => $id], 201);
}
