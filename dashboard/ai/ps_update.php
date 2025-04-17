<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(__DIR__). '/../core/prompt_helper.php';

$page_uid = $_POST['page_uid'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST[$field] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

// var_dump($_POST);
// exit;

// フィールドのバリデーション（安全なカラムのみ許可）
$allowed_fields = ['base_notes', 'amenities_notes', 'rule_notes', 'location_notes', 'appeal_notes', 'others_notes'];
if (!in_array($field, $allowed_fields, true)) {
    die("❌ 無効なフィールド指定です");
}

if (!$page_uid || !$user_uid) {
    die("❌ 情報が足りません");
}

$stmt = $pdo->prepare("UPDATE facility_ai_data SET {$field} = :value WHERE page_uid = :page_uid AND user_uid = :user_uid");
$stmt->execute([
    ':value' => $value,
    ':page_uid' => $page_uid,
    ':user_uid' => $user_uid,
]);

prompt_create($page_uid);


header("Location: index.php?page_uid=" . urlencode($page_uid));
exit;
