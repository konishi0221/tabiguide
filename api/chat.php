<?php
require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';
require_once __DIR__.'/ChatService.php';
header('Content-Type: application/json; charset=utf-8');

try{
  $req = json_decode(file_get_contents('php://input'), true) ?? [];

  $pageUid = $req['pageUid'] ?? null;
  $userId  = $req['userId']  ?? null;
  $mode    = $req['mode']    ?? 'chat';
  $text    = $req['message'] ?? '';

  /* ★ 追加 – ゲスト ctx をまとめる */
  $ctx = [
      'stage'        => $req['stage']        ?? '予約前ゲスト', // pre | stay | post
      'name'         => $req['name']         ?? '',    // 呼び名（任意）
      'booking_name' => $req['bookingName']  ?? '',  // 予約代表者名（任意）
      'room_name'    => $req['roomName']     ?? '',   // ★ 追加
      'charactor'  => $req['charactor']  ?? 'ふつうの丁寧語',   // ★
  ];

  /* 略 … pageUid / userId チェック … */

  /* 3) サービス呼び出し */
  $chat = new ChatService($pageUid, $userId);

  if ($mode === 'history') {
      echo json_encode($chat->getHistory());
  } else {
      $reply = $chat->ask($text, $ctx);   // ← ★ ctx を渡す
      echo json_encode($reply, JSON_UNESCAPED_UNICODE);
  }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString()
    ]);
    error_log($e);           // サーバーログにも残す
}
