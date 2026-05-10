<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function parseInitData(string $initData): array {
    parse_str($initData, $data);
    return $data;
}

function validateTelegramInitData(string $initData): array {
    $data = parseInitData($initData);
    if (!isset($data['hash'])) return [false, null];
    $hash = $data['hash'];
    unset($data['hash']);
    ksort($data);
    $checkString = [];
    foreach ($data as $k => $v) $checkString[] = $k . '=' . $v;
    $checkString = implode("\n", $checkString);

    $secret = hash_hmac('sha256', BOT_TOKEN, 'WebAppData', true);
    $calculated = hash_hmac('sha256', $checkString, $secret);

    if (!hash_equals($calculated, $hash)) return [false, null];
    return [true, $data];
}
