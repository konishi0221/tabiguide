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

    /* ① MIME 判定して GD の create 関数を選択 */
    $mime = mime_content_type($tmpPath);
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($tmpPath); break;
        case 'image/png' : $image = imagecreatefrompng ($tmpPath); break;
        case 'image/gif' : $image = imagecreatefromgif ($tmpPath); break;
        // case 'image/webp' : $image = imagecreatefromgif ($tmpPath); break;
        default:
            die('対応していない画像形式です');   // heic 等はエラー
    }
    if (!$image) die('画像の読み込みに失敗しました');

    /* ② 元サイズ取得＆中央正方形トリミング */
    $w = imagesx($image);
    $h = imagesy($image);
    $side = min($w, $h);             // 正方形一辺
    $srcX = intval(($w - $side) / 2); // 中央寄せ
    $srcY = intval(($h - $side) / 2);

    /* ③ 150×150 にリサンプル */
    $dst = imagecreatetruecolor(150, 150);
    imagecopyresampled(
        $dst, $image,
        0, 0,            // dst x,y
        $srcX, $srcY,    // src x,y
        150, 150,        // dst w,h
        $side, $side     // src w,h
    );

    /* ④ PNG に統一 & base64 */
    ob_start();
    imagepng($dst);
    $base64 = base64_encode(ob_get_clean());

    imagedestroy($image);
    imagedestroy($dst);
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
