<?php
require_once '../../db.php';

// UID を生成（8桁のランダムな英数字）
function generateUid($length = 8) {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// 画像ディレクトリ
$uploadDir = '../../assets/uploads/';

// UID の取得
$uid = isset($_POST['uid']) ? $_POST['uid'] : generateUid();

// `name` が空ならデフォルト値を設定
$name = !empty($_POST['name']) ? $_POST['name'] : '';

// 新規 or 更新処理
if (!empty($_POST['id'])) {
    // 更新処理
    $stmt = $mysqli->prepare("UPDATE stores SET name=?, category_id=?, description=?, is_visible=?, uid=? WHERE id=?");
    $stmt->bind_param("sisssi", $name, $_POST['category_id'], $_POST['description'], $_POST['is_visible'], $uid, $_POST['id']);
} else {
    // 新規追加処理（UIDをINSERT）
    $stmt = $mysqli->prepare("INSERT INTO stores (name, category_id, description, is_visible, uid) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $name, $_POST['category_id'], $_POST['description'], $_POST['is_visible'], $uid);
}

$stmt->execute();
header("Location: list.php");
exit();
