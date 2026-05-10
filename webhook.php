<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/elo.php';
require_once __DIR__ . '/lib/config.php';

$pdo = db();
$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;
$msg = $update['message'];
$tgId = (int)$msg['from']['id'];
$text = trim((string)($msg['text'] ?? ''));
$chatId = (int)$msg['chat']['id'];

$u = $pdo->prepare('INSERT INTO users (tg_id, username, solved_json, last_login) VALUES (?, ?, JSON_ARRAY(), NOW()) ON DUPLICATE KEY UPDATE username=VALUES(username), last_login=NOW()');
$u->execute([$tgId, $msg['from']['username'] ?? null]);

function tgSend(int $chatId, string $text): void {
    $ch = curl_init(TELEGRAM_API . 'sendMessage');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]]);
    curl_exec($ch); curl_close($ch);
}

if ($text === '/start') {
    tgSend($chatId, "Минимальный LMS запущен.\n🎯 /train — тренировка\n⚔️ /duel — дуэль");
    exit;
}
if ($text === '/train') {
    $q = $pdo->prepare('SELECT elo, solved_json FROM users WHERE tg_id=?'); $q->execute([$tgId]); $usr = $q->fetch();
    $solved = json_decode($usr['solved_json'] ?: '[]', true) ?: [];
    $sql = 'SELECT id,text FROM tasks WHERE difficulty_elo BETWEEN ? AND ?'; $params=[(int)$usr['elo']-200,(int)$usr['elo']+200];
    if ($solved){$in=implode(',',array_fill(0,count($solved),'?'));$sql.=" AND id NOT IN ($in)";$params=array_merge($params,$solved);} $sql.=' ORDER BY RAND() LIMIT 1';
    $st=$pdo->prepare($sql);$st->execute($params);$task=$st->fetch();
    if(!$task){tgSend($chatId,'Нет задач в диапазоне ELO.');exit;}
    $pdo->prepare('UPDATE users SET current_task_id=?, current_state=? WHERE tg_id=?')->execute([(int)$task['id'],'solving',$tgId]);
    tgSend($chatId,"🎯 Задача #{$task['id']}\n{$task['text']}");
    exit;
}

'trigger';
