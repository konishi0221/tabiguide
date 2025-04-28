<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// config.phpのパスを修正
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/config.php';

// POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 必要なパラメータのチェック
error_log("FILES: " . print_r($_FILES, true));
error_log("POST: " . print_r($_POST, true));

if (!isset($_FILES['image']) || !isset($_POST['type']) || !isset($_POST['page_uid'])) {
    $missing = [];
    if (!isset($_FILES['image'])) $missing[] = 'image';
    if (!isset($_POST['type'])) $missing[] = 'type';
    if (!isset($_POST['page_uid'])) $missing[] = 'page_uid';
    
    error_log("Missing parameters: " . implode(', ', $missing));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: ' . implode(', ', $missing)]);
    exit;
}

$image = $_FILES['image'];
$type = $_POST['type'];
$page_uid = $_POST['page_uid'];

// 画像アップロードエラーのチェック
if ($image['error'] !== UPLOAD_ERR_OK) {
    $error_message = match($image['error']) {
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        default => 'Unknown upload error'
    };
    error_log("Upload error: " . $error_message);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// 画像タイプの検証
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($image['type'], $allowed_types)) {
    error_log("Invalid file type: " . $image['type']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type: ' . $image['type']]);
    exit;
}

// ファイルサイズの検証（5MB）
if ($image['size'] > 5 * 1024 * 1024) {
    error_log("File too large: " . $image['size'] . " bytes");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit;
}

// 保存ディレクトリのパス設定
$base_dir = $_SERVER['DOCUMENT_ROOT'];
$relative_dir = "/upload/" . $page_uid . "/images";
$upload_dir = $base_dir . $relative_dir;
$filename = $type . '.jpg';  // icon.jpg または background.jpg
$filepath = $upload_dir . '/' . $filename;

error_log("Upload path details:");
error_log("Base directory: " . $base_dir);
error_log("Relative directory: " . $relative_dir);
error_log("Upload directory: " . $upload_dir);
error_log("File path: " . $filepath);
error_log("Temporary file: " . $image['tmp_name']);

// 保存ディレクトリの作成
if (!file_exists($upload_dir)) {
    error_log("Creating directory: " . $upload_dir);
    if (!mkdir($upload_dir, 0777, true)) {
        $error = error_get_last();
        error_log("Failed to create directory: " . $upload_dir . " - Error: " . ($error ? $error['message'] : 'Unknown error'));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory: ' . $error['message']]);
        exit;
    }
    chmod($upload_dir, 0777);
    error_log("Directory created successfully");
}

// ディレクトリのパーミッションを確認
if (!is_writable($upload_dir)) {
    error_log("Directory not writable: " . $upload_dir);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
    exit;
}

// 既存のファイルを削除
if (file_exists($filepath)) {
    if (!unlink($filepath)) {
        error_log("Failed to delete existing file: " . $filepath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete existing file']);
        exit;
    }
}

// 画像のリサイズと保存
try {
    // 元画像の読み込み
    $source_image = null;
    switch($image['type']) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($image['tmp_name']);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($image['tmp_name']);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($image['tmp_name']);
            break;
    }

    if (!$source_image) {
        throw new Exception('Failed to create image resource');
    }

    // 元のサイズを取得
    $width = imagesx($source_image);
    $height = imagesy($source_image);

    // リサイズ後のサイズを計算
    $max_size = ($type === 'icon') ? 200 : 1200;
    if ($width > $max_size || $height > $max_size) {
        if ($width > $height) {
            $new_width = $max_size;
            $new_height = floor($height * ($max_size / $width));
        } else {
            $new_height = $max_size;
            $new_width = floor($width * ($max_size / $height));
        }
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    // 新しい画像を作成
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // 背景を白で塗りつぶす（透明部分の処理）
    $white = imagecolorallocate($new_image, 255, 255, 255);
    imagefill($new_image, 0, 0, $white);

    // リサイズ
    imagecopyresampled(
        $new_image, $source_image,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    );

    // JPGとして保存（高品質）
    imagejpeg($new_image, $filepath, 95);

    // リソースの解放
    imagedestroy($source_image);
    imagedestroy($new_image);

    // 成功レスポンス
    echo json_encode([
        'success' => true,
        'url' => "/upload/{$page_uid}/images/{$filename}"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process image: ' . $e->getMessage()
    ]);
} 