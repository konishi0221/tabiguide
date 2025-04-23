<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

header('Content-Type: text/plain'); // デバッグ中なら一旦プレーン表示にする

// セッションユーザー確認
$user_uid = $_SESSION['user']['uid'] ?? null;
if (!$user_uid) {
    die("ログインしていません");
}

// POST値取得
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$base64 = null;

// 画像がアップロードされているか確認
if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['icon']['tmp_name'];

    // 画像の読み込み
    $image = imagecreatefromstring(file_get_contents($tmpPath));
    if (!$image) {
        die('画像の読み込みに失敗しました');
    }

    // 元画像サイズ取得
    $width = imagesx($image);
    $height = imagesy($image);

    // 正方形にリサイズ（150px x 150px）
    $newSize = 150;
    $resized = imagecreatetruecolor($newSize, $newSize);
    imagecopyresampled(
        $resized, $image,
        0, 0, 0, 0,
        $newSize, $newSize,
        $width, $height
    );

    // PNGとして出力＆base64変換
    ob_start();
    imagepng($resized);
    $imageData = ob_get_clean();
    $base64 = base64_encode($imageData);
}

// SQL作成（icon_base64がある場合のみ更新）
$sql = "UPDATE users SET name = :name, email = :email";
$params = [
    ':name' => $name,
    ':email' => $email,
    ':uid' => $user_uid
];

if ($base64) {
    $sql .= ", icon_base64 = :icon";
    $params[':icon'] = $base64;
}

$sql .= " WHERE uid = :uid";

$stmt = $pdo->prepare($sql);
$success = $stmt->execute($params);

if (!$success) {
    echo "更新に失敗しました：";
    print_r($stmt->errorInfo());
    exit;
}

// セッション情報更新
$_SESSION['user']['name'] = $name;
$_SESSION['user']['email'] = $email;
if ($base64) {
    $_SESSION['user']['icon_base64'] = $base64;
}

// 完了後リダイレクト
header("Location: /dashboard/account/index.php?success=1");
exit;
