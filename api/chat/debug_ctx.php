<?php


require_once __DIR__.'/CtxStore.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageUid = $_GET['p'] ?? 'DEMO';
$userId  = $_GET['u'] ?? 'TEST';

$ctxStore = new CtxStore($pageUid, $userId);
header('Content-Type: application/json');
echo json_encode($ctxStore->load(), JSON_UNESCAPED_UNICODE);
