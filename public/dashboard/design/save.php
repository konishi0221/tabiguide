<?php
require_once __DIR__ . '/../../core/dashboard_head.php';

$page_uid = $_POST['page_uid'] ?? '';
if (!$page_uid) {
  header('Location: index.php?page_uid=' . urlencode($page_uid) . '&error=1');
  exit;
}

// フォームのデータ取得
$data = [
  'primary_color'            => $_POST['primary_color'] ?? '#000000',
  'secondary_color'          => $_POST['secondary_color'] ?? '#ffffff',
  'background_color'         => $_POST['background_color'] ?? '#ffffff',
  'text_color'               => $_POST['text_color'] ?? '#333333',
  'chat_bubble_color_user'   => $_POST['chat_bubble_color_user'] ?? '#cccccc',
  'chat_bubble_color_ai'     => $_POST['chat_bubble_color_ai'] ?? '#eeeeee',
  'font_family'              => $_POST['font_family'] ?? 'system-ui, sans-serif',
  'button_radius'            => $_POST['button_radius'] ?? 8,
  'dark_mode'                => $_POST['dark_mode'] ?? 0,
];

function resizeTo50x50($tmpPath) {
    $imgInfo = getimagesize($tmpPath);
    if (!$imgInfo) return false;

    $mime = $imgInfo['mime'];
    switch ($mime) {
        case 'image/png':
            $src = imagecreatefrompng($tmpPath);
            break;
        case 'image/jpeg':
            $src = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($tmpPath);
            break;
        default:
            return false; // 未対応形式
    }

    if (!$src) return false;

    $resized = imagecreatetruecolor(60, 60);

    // PNGとGIFは透過対応
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefill($resized, 0, 0, $transparent);
    }

    imagecopyresampled($resized, $src, 0, 0, 0, 0, 60, 60, imagesx($src), imagesy($src));

    ob_start();
    imagepng($resized); // 常に PNG で保存（互換性と透過保持）
    $imageData = ob_get_clean();

    imagedestroy($src);
    imagedestroy($resized);

    return base64_encode($imageData);
}

// アイコン画像をbase64として取得（logo_imageのみ）
$images = [
  'logo_base64' => '' // ← これを入れておくと不足しない
];
if (!empty($_FILES['logo_image']['tmp_name'])) {
    // 新しい画像をアップロードしたときだけ base64 に変換
    $resizedBase64 = resizeTo50x50($_FILES['logo_image']['tmp_name']);
    if ($resizedBase64) {
        $images['logo_base64'] = $resizedBase64;
    } else {
        // エラー処理を入れてもOK（形式が違うなど）
        $images['logo_base64'] = null;
    }
} else {
    // アップロードされてない場合、既存の画像をそのまま使う
    $stmt = $pdo->prepare("SELECT logo_base64 FROM design WHERE page_uid = ?");
    $stmt->execute([$page_uid]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    $images['logo_base64'] = $existing['logo_base64'] ?? null;
}

// データを結合
$merged = array_merge($data, $images);

// データ存在チェック
$stmt = $pdo->prepare("SELECT COUNT(*) FROM design WHERE page_uid = ?");
$stmt->execute([$page_uid]);
$exists = $stmt->fetchColumn() > 0;

// SQL生成
if ($exists) {
  $sql = "UPDATE design SET
    primary_color = :primary_color,
    secondary_color = :secondary_color,
    background_color = :background_color,
    text_color = :text_color,
    chat_bubble_color_user = :chat_bubble_color_user,
    chat_bubble_color_ai = :chat_bubble_color_ai,
    font_family = :font_family,
    button_radius = :button_radius,
    dark_mode = :dark_mode,
    logo_base64 = :logo_base64,
    updated_at = NOW()
    WHERE page_uid = :page_uid";
} else {
  $sql = "INSERT INTO design (
    page_uid, primary_color, secondary_color, background_color, text_color,
    chat_bubble_color_user, chat_bubble_color_ai, font_family, button_radius,
    dark_mode, logo_base64
  ) VALUES (
    :page_uid, :primary_color, :secondary_color, :background_color, :text_color,
    :chat_bubble_color_user, :chat_bubble_color_ai, :font_family, :button_radius,
    :dark_mode, :logo_base64
  )";
}

// 実行
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge(['page_uid' => $page_uid], $merged));

header('Location: index.php?page_uid=' . urlencode($page_uid) . '&success=1');
exit;
