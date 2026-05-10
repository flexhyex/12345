<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/api.php';
$pdo = db();
$user = authUserOrFail($pdo);

$type = isset($_GET['type']) ? max(1, min(27, (int)$_GET['type'])) : null;
$elo = (int)$user['elo'];
$solved = json_decode($user['solved_json'] ?: '[]', true) ?: [];

$sql = 'SELECT id, type, text, difficulty_elo FROM tasks WHERE difficulty_elo BETWEEN ? AND ?';
$params = [$elo - 200, $elo + 200];
if ($type !== null) {
    $sql .= ' AND type = ?';
    $params[] = $type;
}
if (!empty($solved)) {
    $in = implode(',', array_fill(0, count($solved), '?'));
    $sql .= " AND id NOT IN ($in)";
    $params = array_merge($params, $solved);
}
$sql .= ' ORDER BY RAND() LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$task = $stmt->fetch();
if (!$task) jsonResponse(['ok' => false, 'error' => 'No task found in current elo range'], 404);

$save = $pdo->prepare('UPDATE users SET current_task_id=?, current_state=? WHERE tg_id=?');
$save->execute([(int)$task['id'], 'solving', (int)$user['tg_id']]);

jsonResponse(['ok' => true, 'task' => $task]);
