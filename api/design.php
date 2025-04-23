<?php
/*  /api/design.php
    ──────────────────────────────────────────
    デザイン（チャット性格・あいさつ文）を取得するだけの API
    GET パラメータ
      ?page_uid=XXXXX
    返り値
      {"page_uid":"...","chat_charactor":"...","chat_first_message":"..."}
    ────────────────────────────────────────── */

    require_once dirname(__DIR__) . '/public/core/config.php';
    require_once dirname(__DIR__) . '/public/core/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');   // dev 用 CORS

$pageUid = $_GET['page_uid'] ?? '';
if ($pageUid === '') {
    http_response_code(400);
    echo json_encode(['error'=>'page_uid が必要です'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* design テーブルから 1 行取得 */
$stmt = $pdo->prepare(
  'SELECT *
     FROM design
    WHERE page_uid = ?
    LIMIT 1'
);
$stmt->execute([$pageUid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/* レコードが無い場合はデフォルト値を返す */
if (!$row) {
    $row = [
        'chat_charactor'     => 'ふつうの丁寧語',
        'chat_first_message' => 'こんにちは！ご質問があればどうぞ！'
    ];
}

$row['page_uid'] = $pageUid;

echo json_encode($row, JSON_UNESCAPED_UNICODE);
