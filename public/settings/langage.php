<?php
session_start();

if (!empty($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
}

ob_start();

$motourl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/index.php'; // デフォルトURLを設定
header("Location: " . $motourl);
exit();
