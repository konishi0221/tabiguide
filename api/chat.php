<?php
session_start();

require_once __DIR__.'/cros.php';

require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';
require_once __DIR__.'/ChatService.php';

try{
  error_log("Received raw input: " . file_get_contents('php://input'));
  
  $req = json_decode(file_get_contents('php://input'), true) ?? [];
  error_log("Decoded request: " . print_r($req, true));

  $pageUid = $req['pageUid'] ?? null;
  $userId  = $req['userId']  ?? null;
  $mode    = $req['mode']    ?? 'chat';
  $text    = $req['message'] ?? '';

  error_log("Processing request - pageUid: $pageUid, userId: $userId, mode: $mode, text: $text");

  /* セッションからコンテキストを復元 */
  $savedCtx = $_SESSION["ctx_{$pageUid}_{$userId}"] ?? [];
  error_log("Saved context: " . print_r($savedCtx, true));
  
  /* リクエストのコンテキストとマージ */
  $ctx = array_merge([
      'stage'        => '予約前ゲスト',
      'name'         => '',
      'booking_name' => '',
      'room_name'    => '',
      'charactor'    => 'ふつうの丁寧語',
      'messages'     => []
  ], $savedCtx, [
      'stage'        => $req['stage']        ?? $savedCtx['stage'] ?? '予約前ゲスト',
      'name'         => $req['name']         ?? $savedCtx['name'] ?? '',
      'booking_name' => $req['bookingName']  ?? $savedCtx['booking_name'] ?? '',
      'room_name'    => $req['roomName']     ?? $savedCtx['room_name'] ?? '',
      'charactor'    => $req['charactor']    ?? $savedCtx['charactor'] ?? 'ふつうの丁寧語',
      'messages'     => $req['messages']     ?? []
  ]);

  error_log("Final context: " . print_r($ctx, true));

  /* 3) サービス呼び出し */
  $chat = new ChatService($pageUid, $userId);

  if ($mode === 'history') {
      $response = $chat->getHistory();
      error_log("History response: " . print_r($response, true));
      echo json_encode($response);
  } else {
      $reply = $chat->ask($text, $ctx);   // ← ★ ctx を渡す
      error_log("Chat reply: " . print_r($reply, true));
      echo json_encode($reply, JSON_UNESCAPED_UNICODE);
  }

} catch (Throwable $e) {
    error_log("Error occurred: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString()
    ]);
}
