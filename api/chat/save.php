<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);                      // /workspace
require_once $root.'/api/cros.php';
require_once $root.'/public/core/config.php';
require_once $root.'/public/core/db.php';
require_once $root.'/public/core/functions.php';

$CHAT_PDO = $pdo ?? db();                         // 共通 PDO

/* ---------- 未知質問 ---------- */
function save_unknown(PDO $pdo,string $pageUid, string $userId, string $q, string $tag=''): void
{
    global $CHAT_PDO;
    /* --- 類似質問（80%以上一致）を事前チェック --- */
    $like = substr($q, 0, 20); // 先頭20文字で簡易比較
    $stmt = $CHAT_PDO->prepare(
        'SELECT id,question
           FROM question
          WHERE page_uid = :p
            AND question LIKE :like
          LIMIT 5'
    );
    $stmt->execute([':p'=>$pageUid, ':like'=>"%{$like}%"]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE) as $id => $existing) {
        similar_text($q, $existing, $pct);
        if ($pct >= 80) {          // 80%以上類似なら保存スキップ
            return;
        }
    }
    
    $CHAT_PDO->prepare(
        'INSERT INTO question (page_uid,chat_id,question,answer,tags,state)
         VALUES (:p,:c,:q,"",:t,"new")
         ON DUPLICATE KEY UPDATE
           chat_id = VALUES(chat_id),
           answer  = VALUES(answer),
           tags    = VALUES(tags),
           state   = VALUES(state),
           id      = LAST_INSERT_ID(id)'
    )->execute([':p'=>$pageUid, ':c'=>$userId, ':q'=>$q, ':t'=>$tag]);
}

/* ---------- スタッフ依頼 ---------- */
function save_staff(array $p): void
{
    global $CHAT_PDO;
    $CHAT_PDO->prepare(
        'INSERT INTO staff_requests
           (page_uid,user_id,task,detail,room_name,
            urgency,importance,stage,guest_name,status)
         VALUES
           (:p,:u,:t,:d,:r,:urg,:imp,:s,:g,"open")'
    )->execute([
        ':p'=>$p['page_uid'], ':u'=>$p['user_id'], ':t'=>$p['task'], ':d'=>$p['detail'],
        ':r'=>$p['room_name']??'', ':urg'=>$p['urgency']??'mid', ':imp'=>$p['importance']??'mid',
        ':s'=>$p['stage']??'滞在中ゲスト', ':g'=>$p['guest_name']??''
    ]);
}

/* ---------- 会話ログを 1 ターンずつ追記 ---------- */
function save_chat_log(array $d): string
{
    global $CHAT_PDO;

    $d += [
        'chat_id' => random(12),
        'room_id' => null,
        'state'   => 'done',
        'conversation' => '[]'          // 例: [{"role":...},{"role":...}]
    ];

    /* 既存ログを取得してマージ */
    $row = $CHAT_PDO->prepare(
        'SELECT conversation FROM chat_log WHERE chat_id=? AND page_uid=? LIMIT 1'
    );
    $row->execute([$d['chat_id'], $d['page_uid']]);
    $conv = $row->fetchColumn();
    $list = $conv ? json_decode($conv, true) : [];

    /* 今回 1 ターン分を配列へ追加 */
    $list[] = json_decode($d['conversation'], true);
    $json   = json_encode($list, JSON_UNESCAPED_UNICODE);

    /* UPSERT */
    $CHAT_PDO->prepare(
        'INSERT INTO chat_log
           (chat_id,page_uid,room_id,state,conversation)
         VALUES
           (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           room_id=VALUES(room_id),
           state  =VALUES(state),
           conversation=VALUES(conversation),
           created_at=NOW()'
    )->execute([
        $d['chat_id'], $d['page_uid'], $d['room_id'], $d['state'], $json
    ]);

    return $d['chat_id'];
}
