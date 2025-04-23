<?php
require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // dev 用

// ── 入力取得 ────────────────────────────────
$pageUid = $_GET['page_uid'] ?? '';
$target  = $_GET['lang']     ?? 'ja';     // 例: en, ko, zh‑CN

if ($pageUid === '') {
  http_response_code(400);
  echo '{"error":"page_uid 必須"}';
  exit;
}

// ── DB 取得 ──────────────────────────────────
$stmt = $pdo->prepare(
  'SELECT chat_charactor, chat_first_message
     FROM design
    WHERE page_uid = ?
    LIMIT 1'
);
$stmt->execute([$pageUid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
  'chat_charactor'     => 'ふつうの丁寧語',
  'chat_first_message' => 'こんにちは！ご質問があればどうぞ！'
];

// ── 翻訳ユーティリティ ──────────────────────
function gTranslate(string $text, string $target, string $apiKey): string
{
  if ($target === 'ja') return $text; // 日本語→日本語はスキップ

  $url = 'https://translation.googleapis.com/language/translate/v2';
  $params = http_build_query([
    'key'    => $apiKey,
    'q'      => $text,
    'target' => $target,
    'format' => 'text'
  ]);

  $ch = curl_init("$url?$params");
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 3
  ]);
  $res = curl_exec($ch);
  curl_close($ch);

  if (!$res) return $text;                // タイムアウト時は原文
  $json = json_decode($res, true);
  return $json['data']['translations'][0]['translatedText'] ?? $text;
}

// ── 翻訳実行 ────────────────────────────────
$apiKey = $GOOGLE_MAPS_API_KEY;           // config.php 内で定義済み
$row['chat_charactor']     = gTranslate($row['chat_charactor'],     $target, $apiKey);
$row['chat_first_message'] = gTranslate($row['chat_first_message'], $target, $apiKey);

// ── 出力 ─────────────────────────────────────
echo json_encode($row, JSON_UNESCAPED_UNICODE);
