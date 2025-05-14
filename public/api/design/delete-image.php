<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/gcs_helper.php';  // gcsDelete()

// POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// JSONデータを取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 必要なパラメータのチェック
if (!isset($data['type']) || !isset($data['page_uid'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$type = $data['type'];
$page_uid = $data['page_uid'];

// ---------- GCS オブジェクトキー ----------
$filename = ($type === 'header_logo' || $type === 'icon')
    ? "{$type}.png"
    : "{$type}.jpg";
$key = "upload/{$page_uid}/images/{$filename}";

try {
    gcsDelete($key);   // 存在しなくても OK
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('delete-image.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;