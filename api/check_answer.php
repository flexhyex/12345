<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/api.php';
require_once __DIR__ . '/../lib/elo.php';
$pdo = db();
$user = authUserOrFail($pdo);

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$answer = trim((string)($body['answer'] ?? ''));
$taskId = (int)($body['task_id'] ?? $user['current_task_id'] ?? 0);
if ($answer === '' || $taskId <= 0) jsonResponse(['ok' => false, 'error' => 'Bad payload'], 400);

$t = $pdo->prepare('SELECT id, answer, difficulty_elo FROM tasks WHERE id=?');
$t->execute([$taskId]);
$task = $t->fetch();
if (!$task) jsonResponse(['ok' => false, 'error' => 'Task not found'], 404);

$isCorrect = mb_strtolower($answer) === mb_strtolower(trim((string)$task['answer']));
$rating = EloCalculator::rate((int)$user['elo'], (int)$task['difficulty_elo'], $isCorrect);

$pdo->beginTransaction();
try {
    $solved = json_decode($user['solved_json'] ?: '[]', true) ?: [];
    if ($isCorrect && !in_array($taskId, $solved, true)) $solved[] = $taskId;

    $u = $pdo->prepare('UPDATE users SET elo=?, solved_json=?, current_state=? WHERE tg_id=?');
    $u->execute([$rating['user_after'], json_encode($solved), 'idle', (int)$user['tg_id']]);

    $updTask = $pdo->prepare('UPDATE tasks SET difficulty_elo=? WHERE id=?');
    $updTask->execute([$rating['task_after'], $taskId]);

    $log = $pdo->prepare('INSERT INTO answer_logs (tg_id, task_id, submitted_answer, is_correct, elo_before, elo_after) VALUES (?, ?, ?, ?, ?, ?)');
    $log->execute([(int)$user['tg_id'], $taskId, $answer, $isCorrect ? 1 : 0, $rating['user_before'], $rating['user_after']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonResponse(['ok' => false, 'error' => 'Transaction failed'], 500);
}

jsonResponse(['ok' => true, 'correct' => $isCorrect, 'elo_delta' => $rating['delta_user'], 'new_elo' => $rating['user_after'], 'right_answer' => $task['answer']]);
