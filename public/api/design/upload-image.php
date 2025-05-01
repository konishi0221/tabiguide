<?php
// アップロード制限の設定
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

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
$filename = ($type === 'header_logo' || $type === 'icon') ? $type . '.png' : $type . '.jpg';  // ヘッダーロゴとアイコンはPNG、他はJPG
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
    if ($type === 'icon') {
        $dst_size = 200;              // 仕上がり 200px

        // アイコン用キャンバス（透過 PNG）
        $new_image = imagecreatetruecolor($dst_size, $dst_size);
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
        imagefilledrectangle($new_image, 0, 0, $dst_size, $dst_size, $transparent);
    
        // 元画像を 200px 正方形内にフィット（余白は透明）
        $ratio = min($dst_size / $width, $dst_size / $height);
        $new_width  = (int)($width  * $ratio);
        $new_height = (int)($height * $ratio);
        $dst_x = (int)(($dst_size - $new_width)  / 2);
        $dst_y = (int)(($dst_size - $new_height) / 2);
    
        imagecopyresampled(
            $new_image, $source_image,
            $dst_x, $dst_y,            // キャンバス側の描画開始位置（中央寄せ）
            0, 0,
            $new_width, $new_height,   // リサイズ後
            $width, $height            // 元サイズ
        );
    
        imagepng($new_image, $filepath, 9);
        } else {
        // 他の画像は従来通り
        $max_size = 1200;
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
    }

    // 新しい画像を作成
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // PNGの場合は透明度を保持
    if ($type === 'header_logo' || $type === 'icon') {
        // アルファチャンネルを有効化
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        // 透明な背景を設定
        $transparent = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    } else {
        // JPGの場合は白背景
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $white);
    }

    // リサイズ
    imagecopyresampled(
        $new_image, $source_image,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    );

    // 保存（ヘッダーロゴとアイコンはPNG、その他はJPG）
    if ($type === 'header_logo' || $type === 'icon') {
        imagepng($new_image, $filepath, 9); // 0-9の圧縮レベル、9が最高品質
    } else {
        imagejpeg($new_image, $filepath, 95);
    }

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