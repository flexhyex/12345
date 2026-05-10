<?php
declare(strict_types=1);

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function authUserOrFail(PDO $pdo): array {
    $initData = $_SERVER['HTTP_X_TELEGRAM_INIT_DATA'] ?? '';
    if ($initData === '') jsonResponse(['ok' => false, 'error' => 'Missing init data'], 401);

    require_once __DIR__ . '/auth.php';
    [$valid, $data] = validateTelegramInitData($initData);
    if (!$valid || !isset($data['user'])) jsonResponse(['ok' => false, 'error' => 'Unauthorized'], 401);

    $user = json_decode($data['user'], true);
    if (!isset($user['id'])) jsonResponse(['ok' => false, 'error' => 'Bad user payload'], 400);

    $stmt = $pdo->prepare('INSERT INTO users (tg_id, username, avatar, last_login, solved_json) VALUES (?, ?, ?, NOW(), JSON_ARRAY())
      ON DUPLICATE KEY UPDATE username=VALUES(username), avatar=VALUES(avatar), last_login=NOW()');
    $stmt->execute([(int)$user['id'], $user['username'] ?? null, $user['photo_url'] ?? null]);

    $q = $pdo->prepare('SELECT * FROM users WHERE tg_id = ?');
    $q->execute([(int)$user['id']]);
    return $q->fetch();
}
