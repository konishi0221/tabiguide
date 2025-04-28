<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$store_id     = $_POST['id']          ?? null;
$uid          = $_POST['uid']         ?? null;
$facility_uid = $_POST['facility_uid']?? null;
$mode         = $_POST['mode']        ?? 'delete_store'; // delete_store / delete_image

// 画像パス: /upload/{facility_uid}/stores/{uid}.jpg
$imagePath = dirname(__DIR__, 2) . "/upload/{$facility_uid}/stores/{$uid}.jpg";

/* 画像だけ削除 */
if ($mode === 'delete_image') {
    if ($uid && is_file($imagePath)) unlink($imagePath);
    header("Location: index.php?id={$store_id}&page_uid={$facility_uid}&image_deleted=1");
    exit;
}

/* レコード＋画像削除 */
if ($mode === 'delete_store' && $store_id) {
    $stmt = $pdo->prepare("DELETE FROM stores WHERE id = :id");
    if ($stmt->execute([':id' => $store_id])) {
        if (is_file($imagePath)) unlink($imagePath);
        header("Location: list.php?page_uid={$facility_uid}&deleted=1");
    } else {
        error_log('削除失敗: '.$stmt->errorInfo()[2]);
        header("Location: list.php?page_uid={$facility_uid}&error=1");
    }
    exit;
}
?>
