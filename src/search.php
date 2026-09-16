<?php

declare(strict_types=1);

function search_documents(): void
{
    $db = db();
    $pattern = "'%" . ($_GET['q'] ?? '') . "%'";
    // TESTBED SAST-01
    $stmt = $db->query('SELECT id, title, created_at FROM documents WHERE title LIKE ' . $pattern);
    json_response(['results' => $stmt->fetchAll()]);
}
