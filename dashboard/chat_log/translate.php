<?php
require_once dirname(__DIR__, 2) . '/core/config.php';
ini_set('display_errors', 1); // ←ログ確認したい場合
error_reporting(E_ALL);      // ←全てのエラー出力する

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$conversation = $body['conversation'];

$translated = [];

foreach ($conversation as $msg) {
  $text = $msg['text'] ?? '';
  $response = file_get_contents("https://translation.googleapis.com/language/translate/v2?key={$GOOGLE_MAPS_API_KEY}", false, stream_context_create([
    'http' => [
      'method'  => 'POST',
      'header'  => 'Content-type: application/json',
      'content' => json_encode([
        'q' => $text,
        'target' => 'ja',
        'format' => 'text',
        'source' => '',
      ])
    ]
  ]));

  $result = json_decode($response, true);
  $msg['text'] = $result['data']['translations'][0]['translatedText'] ?? $text;
  $translated[] = $msg;
}

echo json_encode($translated, JSON_UNESCAPED_UNICODE);
