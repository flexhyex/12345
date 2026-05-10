<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/api.php';
$pdo = db();
$user = authUserOrFail($pdo);

$logs = $pdo->prepare('SELECT task_id, is_correct, elo_before, elo_after, created_at FROM answer_logs WHERE tg_id=? ORDER BY id DESC LIMIT 20');
$logs->execute([(int)$user['tg_id']]);
$history = $logs->fetchAll();

jsonResponse(['ok' => true, 'profile' => $user, 'history' => $history]);
