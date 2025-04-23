<?php
require_once __DIR__ . '/../core/store_setting.php';

$page_uid = $_GET['page_uid'] ?? null;

if (!$page_uid) {
    echo "page_uid を指定してください。例: /test/index.php?page_uid=page_xxxxxx";
    exit;
}

previewNearbyStores($page_uid);
?>
