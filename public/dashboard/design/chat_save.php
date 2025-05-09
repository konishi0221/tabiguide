<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

/* チャット設定 保存処理（テキスト＆音声） */

/* POST取得 */
$pageUid            = $_POST['page_uid']            ?? '';
$chatCharactor      = $_POST['chat_charactor']      ?? '';
$chatFirstMessage   = $_POST['first_message']       ?? '';
$voiceFirstMessage  = $_POST['voice_first_message'] ?? '';

if ($pageUid === '' || $chatCharactor === '') {
    http_response_code(400);
    echo 'page_uid と chat_charactor は必須です';
    exit;
}

/* INSERT ... ON DUPLICATE KEY UPDATE */
$sql = <<<SQL
INSERT INTO design
  (page_uid, chat_charactor, chat_first_message, voice_first_message, updated_at)
VALUES
  (:uid,     :charactor,    :first_msg,         :voice_msg,            NOW())
ON DUPLICATE KEY UPDATE
  chat_charactor       = VALUES(chat_charactor),
  chat_first_message   = VALUES(chat_first_message),
  voice_first_message  = VALUES(voice_first_message),
  updated_at           = NOW()
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':uid'       => $pageUid,
    ':charactor' => $chatCharactor,
    ':first_msg' => $chatFirstMessage,
    ':voice_msg' => $voiceFirstMessage
]);

/* 保存後リダイレクト */
header("Location: chat.php?page_uid={$pageUid}&saved=1");
exit;
