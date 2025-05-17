<?php
// api/ctx_update.php
// Force‑update parts of the user context (ctx) via API.
// Example POST   /api/ctx_update.php?mode=room&page_uid=xxx&room_uid=yyy
require_once __DIR__ . '/cros.php';
require_once __DIR__ . '/../public/core/db.php';
require_once __DIR__ . '/chat/CtxStore.php';


header('Content-Type: application/json; charset=utf-8');

// ---------- helpers ----------
function errorJson($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'error'=>$msg]);
  exit;
}
// ---------- input ----------
$mode     = $_GET['mode']     ?? '';
$pageUid  = $_GET['page_uid'] ?? '';
$userId   = $_GET['user_id']  ?? '';   // pass when needed

// ---- debug: log incoming GET params ----
error_log('[ctx_update] ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));

if ($mode === '' || $pageUid === '') errorJson('mode or page_uid missing');

// ---------- ctx store ----------
try {
  $ctx = new CtxStore($pageUid, $userId);

  switch ($mode) {
    case 'room':
      $roomUid = $_GET['room_uid'] ?? '';
      if ($roomUid === '') errorJson('room_uid missing');

      // fetch room name
    //   $pdo = getDB();
      $stmt = $pdo->prepare("SELECT room_name FROM rooms WHERE room_uid = ? LIMIT 1");
      $stmt->execute([$roomUid]);
      $roomName = $stmt->fetchColumn() ?: '';

      error_log("[ctx_update] room_uid={$roomUid} room_name={$roomName}");

    //   store room name in ctx
      $ctx->merge([
        'room_uid'   => $roomUid,
        'room_name' => $roomName,
        'stage' => '宿泊中'
      ]);
      break;

    // future modes can be added here
    default:
      errorJson('unknown mode');
  }

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  errorJson($e->getMessage(), 500);
}