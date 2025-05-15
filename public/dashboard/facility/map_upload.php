<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');

require_once dirname(__DIR__,2).'/core/bootstrap.php';
$pdo = require dirname(__DIR__,2).'/core/db.php';
// require_once dirname(__DIR__,2).'/core/image_helper.php'; // processImage
require_once dirname(__DIR__,2).'/core/gcs_helper.php';   // gcsUpload
require_once dirname(__DIR__,2).'/core/functions.php';  // processImage

try {
    $page_uid = $_POST['page_uid'] ?? '';
    $reqIdx = isset($_POST['index']) ? (int)$_POST['index'] : 0;
    if ($reqIdx < 1 || $reqIdx > 5) {
        throw new RuntimeException('invalid index');
    }
    $f        = $_FILES['map'] ?? null;
    // --- existence check ---
    if (!$page_uid || !$f || $f['error']) {
        throw new RuntimeException('invalid file');
    }

    // --- size & mime validation ---
    if ($f['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('file too large (>8MB)');
    }
    $mime = mime_content_type($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) {
        throw new RuntimeException('unsupported mime: '.$mime);
    }

    // 拡張子保持
    // $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'png';
    $key = $reqIdx . '.jpg';

    // 画像バイナリを読み込み → 3000px以内にリサイズ & JPG化
    $raw = file_get_contents($f['tmp_name']);
    $bin = processImage($raw, 3000);   // returns JPG binary

    // GCSへアップロード
    $path = "upload/{$page_uid}/images/map/{$key}";
    $url  = gcsUpload($bin, $path);

    echo json_encode(['ok'=>1,'map_key'=>$key,'url'=>$url]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>$e->getMessage()]);
    error_log('[map_upload] '.$e->getMessage());
}