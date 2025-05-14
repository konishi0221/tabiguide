<?php
declare(strict_types=1);
// Delegate CORS settings
require_once __DIR__ . '/cros.php';

// Load session and autoloader
require_once dirname(__DIR__) . '/public/core/bootstrap.php';
// Delegate CORS handling
// error_log('[API CHAT] bootstrap & CORS loaded');
require_once __DIR__ . '/chat/ChatService.php';
require_once dirname(__DIR__) . '/public/core/token_usage.php';

   try {
       $req     = json_decode(file_get_contents('php://input'), true) ?? [];
       $pageUid = $req['pageUid'] ?? '';
       $userId  = $req['userId']  ?? '';
       $mode    = $req['mode']    ?? 'chat';      // chat | voice | history
       $text    = $req['message'] ?? '';
   
       // モードをコンストラクタで渡す
       $chat = new ChatService($pageUid, $userId, $mode);
   
      // ctx は渡さず、テキストだけ
   $reply = $chat->ask($text);

   echo json_encode($reply, JSON_UNESCAPED_UNICODE);
   
   } catch (Throwable $e) {
       http_response_code(500);
       echo json_encode([
           'ok'    => false,
           'error' => $e->getMessage(),
           'file'  => $e->getFile(),
           'line'  => $e->getLine()
       ], JSON_UNESCAPED_UNICODE);
   }