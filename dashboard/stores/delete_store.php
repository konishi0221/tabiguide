<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$store_id = $_POST['id'] ?? null;
$uid = $_POST['uid'] ?? null;
$facility_uid = $_POST['facility_uid'] ?? null;
// $page_uid = $_POST['facility_uid'] ?? null;
$mode = $_POST['mode'] ?? 'delete_store'; // delete_store または delete_image


// path設定（画像保存場所） → /upload/{page_uid}/stores/{uid}.jpg
$imagePath = dirname(__DIR__, 2) . "/upload/{$facility_uid}/stores/{$uid}.jpg";

// 削除モード：画像のみ削除
if ($mode === 'delete_image') {
    if ($uid && file_exists($imagePath)) {
        unlink($imagePath);
    }

    header("Location: index.php?id={$store_id}&page_uid={$facility_uid}&image_deleted=1");
    exit;
}

// 削除モード：storeレコードごと削除
if ($mode === 'delete_store' && $store_id) {
    // データ削除
    $stmt = $mysqli->prepare("DELETE FROM stores WHERE id = ? ");
    $stmt->bind_param("i", $store_id);

    if ($stmt->execute()) {
        // 画像もあれば削除
        unlink($imagePath);

        header("Location: list.php?page_uid={$facility_uid}&image_deleted=1");
        exit;
    } else {
        error_log("削除失敗: " . $stmt->error);
        header("Location: list.php?page_uid={$facility_uid}&error=1");
        exit;
    }
}
?>
