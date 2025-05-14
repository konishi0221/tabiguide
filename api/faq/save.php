<?php
declare(strict_types=1);

/* =======================================================================
   /api/faq/save.php   ――  FAQ の CRUD  +  埋め込み自動生成
   ======================================================================= */

$root = dirname(__DIR__, 2);                     // /workspace


require_once $root . '/api/cros.php';
require_once $root . '/public/core/config.php';
require_once $root . '/public/core/db.php';
require_once $root . '/public/core/functions.php';
require_once $root . '/api/chat/AiClient.php';   //  ← 埋め込み用
require_once $root . '/public/core/token_usage.php';

header('Content-Type: application/json; charset=utf-8');

$req  = json_decode(file_get_contents('php://input'), true) ?? [];
$mode = $req['mode'] ?? '';

$pdo  = require $root . '/public/core/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ---------- 埋め込みユーティリティ ---------- */
/**
 * makeEmbedding()
 *   Wraps AiClient embeddings call and ensures cost is recorded via uid.
 */
function makeEmbedding(string $uid, string $text): string
{
    $ai  = new AiClient();
    $res = $ai->embeddings('text-embedding-3-small', $text, ['uid' => $uid]);

    if (isset($res['error'])) {
        error_log('[makeEmbedding] OpenAI err: '.json_encode($res));
        throw new RuntimeException('Embedding API failed');
    }

    return json_encode($res['data'][0]['embedding'] ?? [], JSON_UNESCAPED_UNICODE);
}

try {
    switch ($mode) {

        /* ---------------- ピン切替 ---------------- */
        case 'toggle_pin':
            $pdo->prepare('UPDATE question SET pinned = NOT pinned WHERE id = ?')
                ->execute([$req['id']]);
            break;

        /* ---------------- 削除 -------------------- */
        case 'delete':
            $pdo->prepare('DELETE FROM question WHERE id = ?')
                ->execute([$req['id']]);
            break;

        /* ---------------- 新規作成 ---------------- */
        case 'create':
            // 質問と回答を連結して埋め込む（Q + "\n" + A 形式で統一）
            $vec = makeEmbedding(
                $req['page_uid'],
                $req['question'] . "\n" . ($req['answer'] ?? '')
            );
            $pdo->prepare(
                'INSERT INTO question
                   (page_uid, question, answer, tags, state, embedding)
                 VALUES
                   (:u, :q, :a, :t, "draft", :e)'
            )->execute([
                ':u' => $req['page_uid'],
                ':q' => $req['question'],
                ':a' => $req['answer'] ?? '',
                ':t' => $req['tags']   ?? '',
                ':e' => $vec
            ]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            exit;

        /* ---------------- 更新 -------------------- */
        case 'update':
            /* 毎回作り直しても数 ms 程度なのでシンプルに再生成 */
            // 質問と回答を連結して埋め込む

            error_log('save: ' . $req['page_uid'] . '\n' .  $req['question'] );

            $vec = makeEmbedding($req['page_uid'],  $req['question'] . '\n' .  $req['answer']  );
            $sql = 'UPDATE question
                      SET question = :q,
                          answer   = :a,
                          tags     = :t,
                          embedding= :e,
                          state    = CASE WHEN LENGTH(:ans) > 0 THEN "reply" ELSE state END
                    WHERE id      = :id';
            $pdo->prepare($sql)->execute([
                ':q'   => $req['question'],
                ':a'   => $req['answer'],
                ':ans' => $req['answer'],
                ':t'   => $req['tags'],
                ':e'   => $vec,
                ':id'  => $req['id']
            ]);
            echo '{"ok":true}';
            exit;

        /* ---------------- アーカイブ ---------------- */
        case 'archive':
            $pdo->prepare('UPDATE question SET state = "archive" WHERE id = ?')
                ->execute([$req['id']]);
            break;

        /* -------------- アンアーカイブ -------------- */
        case 'unarchive':
            $pdo->prepare('UPDATE question SET state = "reply" WHERE id = ?')
                ->execute([$req['id']]);
            break;

        /* ---------------- モード不正 ---------------- */
        default:
            throw new RuntimeException('invalid mode');
    }

    echo '{"ok":true}';

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
