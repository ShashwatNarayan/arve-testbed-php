<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');

if ($method === 'POST' && $path === '/docs') {
    upload_document();
} elseif ($method === 'GET' && $path === '/docs/search') {
    search_documents();
} elseif ($method === 'POST' && $path === '/docs/import-url') {
    import_from_url();
} elseif ($method === 'POST' && $path === '/settings/import') {
    import_settings();
} elseif ($method === 'GET' && preg_match('#^/docs/(\d+)/download$#', $path, $m)) {
    download_document((int) $m[1]);
} elseif ($method === 'POST' && preg_match('#^/docs/(\d+)/convert$#', $path, $m)) {
    convert_document((int) $m[1]);
} elseif ($method === 'GET' && preg_match('#^/docs/(\d+)/preview$#', $path, $m)) {
    preview_document((int) $m[1]);
} else {
    error_response('not found', 404);
}
