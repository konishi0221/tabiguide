<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

function generateUid($length = 8) {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

$mode = $_POST['mode'] ?? null;
$facility_uid = $_POST['facility_uid'] ?? null;
$page_uid = $_POST['page_uid'] ?? null;

if (!in_array($mode, ['insert', 'update'])) {
    echo "不正なアクセスです。";
    exit;
}

// 入力値の取得と整形
$id = $_POST['id'] ?? null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$en_name = isset($_POST['en_name']) ? urldecode(base64_decode(trim($_POST['en_name']))) : '';
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
$category = isset($_POST['category']) && $_POST['category'] !== '' ? $_POST['category'] : null;
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 35.711892;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 139.857269;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$encoded = $_POST['en_description'] ?? '';
$en_description = urldecode(base64_decode($encoded));
$is_visible = isset($_POST['is_visible']) ? intval($_POST['is_visible']) : 1;
$uid = isset($_POST['uid']) && !empty($_POST['uid']) ? $_POST['uid'] : generateUid(8);
$url = isset($_POST['url']) ? urldecode(base64_decode(trim($_POST['url']))) : '';

// 画像アップロード処理（upload/page_uid/stores/uid.jpg に保存）
$uploadDir = dirname(__DIR__) . "/../upload/{$facility_uid}/stores/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!empty($_FILES['image']['tmp_name'])) {
    $filePath = $uploadDir . $uid . ".jpg";
    $imageType = exif_imagetype($_FILES['image']['tmp_name']);

    if ($imageType === IMAGETYPE_JPEG || $imageType === IMAGETYPE_PNG) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            chmod($filePath, 0644);
        } else {
            error_log("画像の保存に失敗しました: " . $filePath);
        }
    } else {
        error_log("対応していない画像形式: " . $_FILES['image']['type']);
    }
}

// `insert` or `update` の処理
if ($mode === 'update' && $id) {
    $stmt = $mysqli->prepare("UPDATE stores SET name=?, category=?, lat=?, lng=?, description=?, is_visible=?, url=?, uid=?, en_name=?, en_description=?, facility_uid=? WHERE id=?");
    $stmt->bind_param("ssddsisssssi", $name, $category, $lat, $lng, $description, $is_visible, $url, $uid, $en_name, $en_description, $facility_uid, $id);
} else {
    $stmt = $mysqli->prepare("INSERT INTO stores (name, category, lat, lng, description, is_visible, url, uid, en_name, en_description, facility_uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsisssss", $name, $category, $lat, $lng, $description, $is_visible, $url, $uid, $en_name, $en_description, $facility_uid);
}

if ($stmt->execute()) {
    header("Location: list.php?success=1&page_uid=" . $facility_uid);
    exit();
} else {
    error_log("データベースエラー: " . $stmt->error);
    header("Location: list.php?success=1&page_uid=" . $facility_uid);
    exit();
}
