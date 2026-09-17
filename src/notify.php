<?php

declare(strict_types=1);

const SMTP_HOST = 'smtp.docvault.internal';
const SMTP_USER = 'docvault-notify';
const SMTP_RELAY_TOKEN = 'CSTkj07crCs6d9dTcIZDkcQN8KmmrEWk';

function send_conversion_failed(int $docId, string $recipient): bool
{
    ini_set('SMTP', SMTP_HOST);
    $headers = 'From: docvault@docvault.internal' . PHP_EOL
        . 'X-Relay-Auth: ' . SMTP_USER . ':' . SMTP_RELAY_TOKEN;
    $subject = 'DocVault: conversion of document ' . $docId . ' failed';
    $body = 'The converter exited with an error. See the server log for details.';
    return mail($recipient, $subject, $body, $headers);
}
