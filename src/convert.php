<?php

declare(strict_types=1);

function detect_mime(string $source): string
{
    $command = 'file --brief --mime-type ' . escapeshellarg($source);
    // TESTBED SAFE-02
    $mime = shell_exec($command);
    return trim((string) $mime);
}

function convert_document(int $id): void
{
    $doc = find_document(db(), $id);
    if ($doc === null) {
        error_response('not found', 404);
        return;
    }
    $source = config()['storage_dir'] . '/' . $doc['filename'];
    $format = $_GET['format'] ?? 'html';
    $args = $format . ' ' . escapeshellarg($source);
    // TESTBED SAST-02
    $output = shell_exec('pandoc --to ' . $args);
    if ($output === null || $output === false) {
        error_response('conversion failed', 502);
        return;
    }
    json_response(['id' => $id, 'format' => $format, 'source_type' => detect_mime($source), 'content' => $output]);
}
