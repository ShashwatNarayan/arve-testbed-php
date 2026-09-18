<?php

declare(strict_types=1);

function import_settings(): void
{
    $bundle = file_get_contents('php://input') ?: '';
    // TESTBED SAST-03
    $settings = eval('return ' . $bundle . ';');
    if (!is_array($settings)) {
        error_response('invalid settings bundle', 400);
        return;
    }
    $db = db();
    foreach ($settings as $name => $value) {
        save_setting($db, (string) $name, is_scalar($value) ? (string) $value : json_encode($value));
    }
    json_response(['imported' => count($settings)]);
}
