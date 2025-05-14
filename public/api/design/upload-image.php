<?php
// ---------------------------------------------
//  design/upload-image.php  ― GCS 版
// ---------------------------------------------
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size',        '20M');
ini_set('memory_limit',         '256M');
ini_set('max_execution_time',   '300');
ini_set('max_input_time',       '300');

header('Content-Type: application/json');
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/functions.php';   // processImage()
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/gcs_helper.php';  // gcsUpload(), gcsDelete()

// --- メソッドチェック ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// --- パラメータ取得 ---
if (!isset($_FILES['image'], $_POST['type'], $_POST['page_uid'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$image    = $_FILES['image'];
$type     = $_POST['type'];        // icon | background | header_logo
$page_uid = $_POST['page_uid'];

// 透過 (PNG/GIF) を保持するか判定
$mime = $image['type'] ?? '';
$ext  = strtolower(pathinfo($image['name'] ?? '', PATHINFO_EXTENSION));
$isPng = str_contains($mime, 'png') || str_contains($mime, 'gif') || $ext === 'png' || $ext === 'gif';

// --- アップロードエラー ---
if ($image['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Upload error']);
    exit;
}

// --- バリデーション ---
$allowed_types = [
    'image/jpeg', 'image/pjpeg',
    'image/png',  'image/x-png',
    'image/gif'
];
if (!in_array($image['type'], $allowed_types, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}
if ($image['size'] > 5 * 1024 * 1024) {        // 5 MB
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit;
}

// --- 保存キーと拡張子 ---
// icon / header_logo は常に PNG（透過保持）
// background は元が PNG/GIF なら PNG、それ以外は JPG
if ($type === 'icon' || $type === 'header_logo' || $isPng) {
    $filename = "{$type}.png";
} else {
    $filename = "{$type}.jpg";
}
$key = "upload/{$page_uid}/images/{$filename}";

// --- 画像処理 & アップロード ---
try {
    $bin = file_get_contents($image['tmp_name']);

    // リサイズ・回転補正
    $processed = ($type === 'icon')
        ? processImage($bin, 200, 'square')     // 正方形 200 px
        : processImage($bin, 1200, 'default');  // 最大幅 1200 px

    if ($type === 'icon' || $type === 'header_logo' || $isPng) {
        // 必ず PNG として保存（透過保持）
        $im = imagecreatefromstring($processed);
        imagealphablending($im, false);           // 透過ピクセルを維持
        imagesavealpha($im, true);                // 透過を保持
        ob_start();
        imagepng($im);
        $processed = ob_get_clean();
        imagedestroy($im);
    } else {
        // 背景など JPG 保存
        $im = imagecreatefromstring($processed);
        ob_start();
        imagejpeg($im, null, 95);
        $processed = ob_get_clean();
        imagedestroy($im);
    }

    // 既存オブジェクト削除 → アップロード
    gcsDelete($key);
    $url = gcsUpload($processed, $key);

    echo json_encode(['success' => true, 'url' => $url]);
} catch (Throwable $e) {
    error_log('upload-image.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;