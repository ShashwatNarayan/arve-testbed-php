<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

const PREVIEW_EXTENSIONS = ['txt', 'md'];

function upload_document(): void
{
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error_response('file is required', 400);
        return;
    }
    $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $stored = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    $target = config()['storage_dir'] . '/' . $stored;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        error_response('could not store file', 500);
        return;
    }
    $checksum = hash_file('sha256', $target);
    $title = trim($_POST['title'] ?? '') ?: $_FILES['file']['name'];
    $id = insert_document(db(), $title, $stored, $checksum);
    json_response(['id' => $id, 'checksum' => $checksum, 'download_token' => download_token($id)], 201);
}

function download_document(int $id): void
{
    $doc = find_document(db(), $id);
    if ($doc === null) {
        error_response('not found', 404);
        return;
    }
    $name = $_GET['name'] ?? $doc['filename'];
    // TESTBED SAST-04
    $body = file_get_contents('../storage/files/' . $name);
    if ($body === false) {
        error_response('not found', 404);
        return;
    }
    header('Content-Type: application/octet-stream');
    echo $body;
}

function preview_document(int $id): void
{
    $doc = find_document(db(), $id);
    if ($doc === null) {
        error_response('not found', 404);
        return;
    }
    $name = basename($_GET['name'] ?? $doc['filename']);
    if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), PREVIEW_EXTENSIONS, true)) {
        error_response('preview not available for this file type', 415);
        return;
    }
    $path = config()['storage_dir'] . '/' . $name;
    // TESTBED SAFE-03
    $text = file_get_contents($path);
    if ($text === false) {
        error_response('not found', 404);
        return;
    }
    $markdown = new Parsedown();
    $markdown->setSafeMode(true);
    $twig = new Environment(new FilesystemLoader(config()['template_dir']), ['autoescape' => 'html']);
    header('Content-Type: text/html; charset=utf-8');
    echo $twig->render('preview.html.twig', ['title' => $doc['title'], 'body' => $markdown->text($text)]);
}
