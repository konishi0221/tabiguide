<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_POST['page_uid'] ?? null;

if (!$page_uid) {
    echo "ページUIDが指定されていません。";
    exit;
}

// 店舗情報を取得
$stmt = $pdo->prepare("SELECT id, uid FROM stores WHERE facility_uid = ?");
$stmt->execute([$page_uid]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 店舗削除
$stmt = $pdo->prepare("DELETE FROM stores WHERE facility_uid = ?");
$stmt->execute([$page_uid]);

// 画像削除
$storeImageDir = dirname(__DIR__) . "/../upload/{$page_uid}/stores/";
foreach ($stores as $store) {
    $imgPath = $storeImageDir . $store['uid'] . '.jpg';
    if (file_exists($imgPath)) {
        unlink($imgPath);
    }
}

header("Location: list.php?page_uid=" . urlencode($page_uid) . "&deleted_all=1");
exit;
