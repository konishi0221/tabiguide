<?php
/* =======================================================================
   api/chat.php ― 共通エンドポイント  (mode = chat | voice | history)
   ======================================================================= */
   declare(strict_types=1);
   session_start();
   require_once __DIR__ . '/cros.php';
   require_once __DIR__ . '/chat/ChatService.php';
   
   try {
       $req     = json_decode(file_get_contents('php://input'), true) ?? [];
       $pageUid = $req['pageUid'] ?? '';
       $userId  = $req['userId']  ?? '';
       $mode    = $req['mode']    ?? 'chat';      // chat | voice | history
       $text    = $req['message'] ?? '';
   
       // モードをコンストラクタで渡す
       $chat = new ChatService($pageUid, $userId, $mode);
   
       if ($mode === 'history') {
           echo json_encode($chat->getHistory(), JSON_UNESCAPED_UNICODE);
           exit;
       }
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
   