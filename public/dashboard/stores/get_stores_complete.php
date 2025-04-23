<?php require_once dirname(__DIR__) . '/stores/get_stores.php';

// var_dump((isset($_POST['page_uid'])) );
// exit;

// get_stores_complete.php
if (isset($_POST['page_uid'])) {
    $page_uid = $_POST['page_uid'];
    insertNearbyStores($page_uid); // ← 実行するだけ
}
header("Location: /dashboard/stores/list.php?page_uid={$page_uid}");
exit;
