<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = 'JP';
}

// ---- debug: file reached ----
error_log(sprintf('[%s] dashboard_head.php reached, uid=%s, page_uid=%s',
                  date('Y-m-d H:i:s'),
                  $_SESSION['user']['uid'] ?? 'guest',
                  $_GET['page_uid'] ?? 'none'));

// 未ログインならログインページへリダイレクト
if (!isset($_SESSION['user']['uid'])) {
    header('Location: /login/');
    exit;
}

$currentUser = $_SESSION['user'];

// 必要なファイル読み込み
require_once __DIR__ . '/functions.php';   // 共通関数など

// ユーザー情報を変数化（便利なので）
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/category.php';

if (!empty($_GET['page_uid'])) {
    $page_uid = $_GET['page_uid'];
    $user_uid = $_SESSION['user']['uid'];

    $access_role = getAccessRole($pdo, $page_uid, $user_uid);
    if (!$access_role) {
        error_log(sprintf('[%s] access denied, uid=%s, page_uid=%s',
                          date('Y-m-d H:i:s'),
                          $user_uid,
                          $page_uid));
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
        $facility_type = $base_data['施設タイプ'] ?? '民泊';
    }
}

function getRoomLabel($facilityType) {
  return match ($facilityType) {
    'キャンプ場' => '区画',
    'グランピング' => 'テント',
    default => '部屋'
  };
}
