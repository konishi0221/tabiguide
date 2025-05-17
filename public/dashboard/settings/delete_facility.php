<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('不正なリクエストです');
}

$page_uid = $_POST['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

if (!$page_uid || !$user_uid) {
    die('不正なアクセスです');
}

// 所有権の確認
$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? AND user_uid = ?");
$stmt->execute([$page_uid, $user_uid]);
$facility = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facility) {
    die('施設が見つかりません');
}

// 課金レコード（キャンセル以外）が残っていないか確認
$billingStmt = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE page_uid = ? AND status <> 'canceled'");
$billingStmt->execute([$page_uid]);
$activeBilling = (int)$billingStmt->fetchColumn();

if ($activeBilling > 0) {
    // 有効な課金があるので削除不可
    header("Location: /dashboard/settings/index.php?page_uid=" . urlencode($page_uid) . "&error=billingExist");
    exit;
}

// 関連データ削除
$tables = [
    'stores' => 'facility_uid',
    'question' => 'page_uid',
    'chat_log' => 'page_uid',
    'design' => 'page_uid',
    'rooms' => 'page_uid',
    'staff_requests' => 'page_uid',
    'token_usage' => 'page_uid',
    'facility_ai_data' => 'page_uid'
];

foreach ($tables as $table => $column) {
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
    $stmt->execute([$page_uid]);
}

// ディレクトリ削除
function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

$upload_dir = dirname(__DIR__, 2) . '/upload/' . $page_uid;
deleteDirectory($upload_dir);

// リダイレクト
header("Location: /dashboard/index.php?deleted=1");
exit;
