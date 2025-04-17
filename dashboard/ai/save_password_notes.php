<?php
require_once dirname(__DIR__, 2) . '/core/db.php';

$page_uid = $_POST['page_uid'] ?? '';
$guest_password = trim($_POST['guest_password'] ?? '');
$private_info_notes = trim($_POST['private_info_notes'] ?? '');

// page_uid がない場合はリダイレクトして中止
if (!$page_uid) {
    header("Location: /dashboard/ai/index.php?error=missing_page_uid");
    exit;
}

// DBに保存
$stmt = $pdo->prepare("UPDATE facility_ai_data SET guest_password = ?, private_info_notes = ?, updated_at = NOW() WHERE page_uid = ?");
$stmt->execute([$guest_password, $private_info_notes, $page_uid]);

// 成功時にリダイレクト（AI設定ページに戻す）
header("Location: /dashboard/ai/index.php?page_uid=" . urlencode($page_uid) . "&saved=1");
exit;
