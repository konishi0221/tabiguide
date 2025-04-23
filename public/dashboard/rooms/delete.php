<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$room_uid = $_GET['room_uid'] ?? '';

if (!$page_uid || !$room_uid) {
    die('不正なアクセスです。');
}

// 対象の部屋を削除
$stmt = $pdo->prepare("DELETE FROM rooms WHERE room_uid = ? AND page_uid = ?");
$stmt->execute([$room_uid, $page_uid]);

// 削除後リダイレクト
header("Location: index.php?page_uid={$page_uid}&deleted=1");
exit;
