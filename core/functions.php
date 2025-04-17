<?php
function l($ja, $en) {
  $language = ($_SESSION['lang'] == 'JP') ? $ja : (isset($_SESSION['lang']) ? $en : $en);
  print $language;
}

function dd($text) {
  var_dump($text);
  exit;
}

function random() {
  $str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
  return substr(str_shuffle($str), 0, 10);
}

function getAccessRole($pdo, $page_uid, $user_uid) {
    // オーナー判定
    $stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? AND user_uid = ? LIMIT 1");
    $stmt->execute([$page_uid, $user_uid]);
    if ($stmt->fetch()) return 'owner';

    // 共同管理者 or スタッフ判定
    $stmt = $pdo->prepare("SELECT managers_json FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
    $stmt->execute([$page_uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;

    $managers = json_decode($row['managers_json'], true);
    foreach ($managers as $manager) {
        if ($manager['uid'] === $user_uid) {
            return $manager['role']; // 'manager' or 'staff'
        }
    }

    return false; // アクセス権なし
}
