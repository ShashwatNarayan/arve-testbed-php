<?php

declare(strict_types=1);

function find_document(PDO $db, int $id): ?array
{
    // TESTBED SAFE-01
    $stmt = $db->prepare('SELECT id, title, filename, checksum, created_at FROM documents WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function insert_document(PDO $db, string $title, string $filename, string $checksum): int
{
    $stmt = $db->prepare(
        'INSERT INTO documents (title, filename, checksum, created_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$title, $filename, $checksum, gmdate('c')]);
    return (int) $db->lastInsertId();
}

function save_setting(PDO $db, string $name, string $value): void
{
    $stmt = $db->prepare('INSERT OR REPLACE INTO settings (name, value) VALUES (?, ?)');
    $stmt->execute([$name, $value]);
}
