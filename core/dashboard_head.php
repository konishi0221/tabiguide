<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = 'JP';
}

// 未ログインならログインページへリダイレクト
if (!isset($_SESSION['user']['uid'])) {
    header('Location: /login/');
    exit;
}

$currentUser = $_SESSION['user'];

// 必要なファイル読み込み
require_once __DIR__ . '/../core/functions.php';   // 共通関数など

// ユーザー情報を変数化（便利なので）
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/category.php';

if (!empty($_GET['page_uid'])) {
    $page_uid = $_GET['page_uid'];
    $user_uid = $_SESSION['user']['uid'];

    $access_role = getAccessRole($pdo, $page_uid, $user_uid);
    if (!$access_role) {
        header('Location: /dashboard/');
        exit;
    }

    // 必要ならグローバル変数にしておく
    $GLOBALS['current_access_role'] = $access_role;
}

$facility_type = '';
if (!empty($_GET['page_uid'])) {
    $stmt = $pdo->prepare("SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
    $stmt->execute([$_GET['page_uid']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $base_data = json_decode($row['base_data'], true);
        $facility_type = $base_data['基本情報']['施設タイプ'] ?? '民泊';
    }
}

function getRoomLabel($facilityType) {
  return match ($facilityType) {
    'キャンプ場' => '区画',
    'グランピング' => 'テント',
    default => '部屋'
  };
}
