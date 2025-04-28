<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/config.php';

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

// ファイルパスの設定
$base_dir = $_SERVER['DOCUMENT_ROOT'];
$relative_dir = "/upload/" . $page_uid . "/images";
$filepath = $base_dir . $relative_dir . '/' . $type . '.jpg';

error_log("Delete path details:");
error_log("File path: " . $filepath);

// ファイルの存在確認
if (!file_exists($filepath)) {
    error_log("File does not exist: " . $filepath);
    echo json_encode(['success' => true, 'message' => 'File already deleted']);
    exit;
}

// ファイルの削除
try {
    if (unlink($filepath)) {
        error_log("File deleted successfully: " . $filepath);
        echo json_encode(['success' => true]);
    } else {
        error_log("Failed to delete file: " . $filepath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
    }
} catch (Exception $e) {
    error_log("Error deleting file: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting file: ' . $e->getMessage()
    ]);
} 