<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';

$mode = $_POST['mode'] ?? 'insert';
$page_uid = $_POST['page_uid'] ?? '';
$room_uid = $_POST['room_uid'] ?? '';
$room_name = trim($_POST['room_name'] ?? '');
$room_type = trim($_POST['room_type'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

$amenities_data_raw = $_POST['amenities_data_json'] ?? '{}';
$amenities_data = json_decode($amenities_data_raw, true);
$amenities_data_encoded = json_encode($amenities_data, JSON_UNESCAPED_UNICODE);



// var_dump($amenities_data_encoded);
// exit;
if (!$page_uid || !$room_name) {
    die('必須項目が不足しています。');
}

if ($mode === 'update' && $room_uid) {
    // 更新処理
    $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, room_type = ?, capacity = ?, notes = ?, amenities_data = ?, updated_at = NOW() WHERE room_uid = ?");
    $stmt->execute([$room_name, $room_type, $capacity, $notes, $amenities_data_encoded, $room_uid]);
} else {
    // 新規作成
    $room_uid = uniqid();
    $stmt = $pdo->prepare("INSERT INTO rooms (page_uid, room_uid, room_name, room_type, capacity, notes, amenities_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$page_uid, $room_uid, $room_name, $room_type, $capacity, $notes, $amenities_data_encoded]);
}

// 完了後リダイレクト
header("Location: index.php?page_uid={$page_uid}&success=1");
exit;
