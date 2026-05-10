<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
?><!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EGE LMS</title>
<style>
:root{--bg:var(--tg-theme-bg-color,#111);--text:var(--tg-theme-text-color,#eee);--btn:var(--tg-theme-button-color,#3b82f6)}
body{margin:0;background:var(--bg);color:var(--text);font-family:system-ui;padding:16px}.card{background:#1b1b1b;padding:12px;border-radius:12px;margin-bottom:12px}
button{border:0;padding:10px 14px;border-radius:10px;background:var(--btn);color:#fff}
textarea{width:100%;min-height:80px}
</style></head>
<body>
<div class="card"><h3>📈 Профиль</h3><div id="profile"></div></div>
<div class="card"><button id="start">🎯 Start Training</button> <select id="type"></select><div id="task"></div><textarea id="answer" placeholder="Ваш ответ"></textarea></div>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script>
const tg = window.Telegram.WebApp; tg.expand();
const headers = {'X-Telegram-Init-Data': tg.initData};
for(let i=1;i<=27;i++){const o=document.createElement('option');o.value=i;o.textContent='Задание '+i;type.appendChild(o)}
async function api(url,opt={}){opt.headers={...(opt.headers||{}),...headers};const r=await fetch(url,opt);return r.json();}
async function loadProfile(){const d=await api('../api/get_profile.php');profile.textContent=d.ok?`ELO: ${d.profile.elo} | level: ${d.profile.level}`:'Auth error';}
start.onclick=async()=>{const d=await api('../api/get_task.php?type='+type.value);if(!d.ok){task.textContent=d.error;return;}task.dataset.id=d.task.id;task.innerHTML=`<p><b>#${d.task.id}</b> ${d.task.text}</p>`;tg.MainButton.setText('Submit Answer');tg.MainButton.show();};
tg.MainButton.onClick(async()=>{const d=await api('../api/check_answer.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({task_id:task.dataset.id,answer:answer.value})});alert(d.correct?`Верно! +${d.elo_delta}`:`Ошибка! ${d.right_answer}`);loadProfile();});
loadProfile();
</script></body></html>
