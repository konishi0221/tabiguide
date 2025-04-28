<?php
declare(strict_types=1);
require_once __DIR__ . '/cros.php';


require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';
include_once(  dirname(__DIR__) . '/public/core/functions.php');

function save_unknown(
    PDO $pdo,
    string $pageUid,
    string $userId,
    string $question,
    string $tags = ''
): void
{
    $sql = 'INSERT INTO question
              (page_uid, chat_id, question, answer, tags, state)
            VALUES
              (:p, :c, :q, "", :t, "new")';

    $pdo->prepare($sql)->execute([
        ':p' => $pageUid,
        ':c' => $userId,
        ':q' => $question,
        ':t' => $tags
    ]);
}



function save_staff(PDO $pdo, array $payload): void
{
    $sql = 'INSERT INTO staff_requests
              (page_uid, user_id, task, detail, room_name,
               urgency, importance, stage, guest_name, status)
            VALUES
              (:p, :u, :t, :d, :r,
               :urg, :imp, :s, :g, "open")';

    $pdo->prepare($sql)->execute([
        ':p'   => $payload['page_uid'],
        ':u'   => $payload['user_id'],
        ':t'   => $payload['task'],
        ':d'   => $payload['detail'],
        ':r'   => $payload['room_name'] ?? '',
        ':urg' => $payload['urgency']   ?? 'mid',
        ':imp' => $payload['importance']?? 'mid',
        ':s'   => $payload['stage']     ?? '滞在中ゲスト',
        ':g'   => $payload['guest_name']?? ''
    ]);
}



/* -----------------------------------------------------------
   チャット履歴 Upsert → chat_log
   ----------------------------------------------------------- */
   function save_chat_log(
       PDO $pdo,
       ?string $chatId,
       string $pageUid,
       ?string $roomId,
       string $state,
       string $conversationJson
   ): string {

       $chatId = $chatId ?: random();     // ← 未指定なら発番

       $sql = 'INSERT INTO chat_log
                 (chat_id, page_uid, room_id, state, conversation)
               VALUES
                 (:id, :p, :r, :s, :c)
               ON DUPLICATE KEY UPDATE
                 room_id      = VALUES(room_id),
                 state        = VALUES(state),
                 conversation = VALUES(conversation),
                 created_at   = NOW()';

       $pdo->prepare($sql)->execute([
           ':id' => $chatId,
           ':p'  => $pageUid,
           ':r'  => $roomId,
           ':s'  => $state,
           ':c'  => $conversationJson
       ]);

       return $chatId;                         // 呼び出し側に返す
   }
