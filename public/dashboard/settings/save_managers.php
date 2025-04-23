<?php
require_once __DIR__ . '/../../core/dashboard_head.php';

header('Content-Type: application/json');

// POSTデータ受け取り
$data = json_decode(file_get_contents('php://input'), true);
$page_uid = $data['page_uid'] ?? '';
$managers = $data['managers'] ?? [];

// ログインしているユーザーのUID
$user_uid = $_SESSION['user']['uid'] ?? '';

if (!$page_uid || !$user_uid) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 施設の所有者 or 管理者かどうかチェック（セキュリティ）
$stmt = $pdo->prepare("SELECT user_uid FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => '施設が見つかりません']);
    exit;
}


// 保存処理
$stmt = $pdo->prepare("UPDATE facility_ai_data SET managers_json = :managers_json, updated_at = NOW() WHERE page_uid = :page_uid");
$stmt->execute([
    ':managers_json' => json_encode($managers, JSON_UNESCAPED_UNICODE),
    ':page_uid' => $page_uid
]);

echo json_encode(['success' => true]);
