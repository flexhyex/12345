<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';

$pdo = db();
$start = isset($_GET['from']) ? (int)$_GET['from'] : 1;
$end = isset($_GET['to']) ? (int)$_GET['to'] : 27;
$limit = isset($_GET['limit']) ? max(1, min(5, (int)$_GET['limit'])) : 3;

function baseElo(int $type): int {
    if ($type <= 10) return 1000 + ($type - 1) * 40;
    if ($type >= 24) return 2000 + ($type - 24) * 100;
    return 1400 + ($type - 11) * 45;
}

for ($n = $start; $n <= $end; $n++) {
    $url = "https://kompege.ru/api/v1/task/number/$n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $items = json_decode((string)$raw, true);
    if (!is_array($items)) continue;

    $count = 0;
    foreach ($items as $task) {
        $id = (int)($task['id'] ?? 0);
        if ($id <= 0) continue;
        $text = strip_tags((string)($task['text'] ?? ''), '<code><pre><b><i><strong><em><br>');
        $answer = trim((string)($task['answer'] ?? ''));
        if ($answer === '') continue;
        $img = isset($task['images']) ? json_encode($task['images']) : null;

        $stmt = $pdo->prepare('INSERT INTO tasks (id, type, text, answer, difficulty_elo, images_json, source_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE text=VALUES(text), answer=VALUES(answer), images_json=VALUES(images_json), source_url=VALUES(source_url)');
        $stmt->execute([$id, $n, $text, $answer, baseElo($n), $img, $url]);

        $count++;
        if ($count >= $limit) break;
    }
    echo "Imported type $n: $count\n";
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }
}
