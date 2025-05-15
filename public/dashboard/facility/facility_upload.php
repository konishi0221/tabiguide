<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/functions.php';  // processImage()
require_once dirname(__DIR__, 2) . '/core/gcs_helper.php'; // gcsUpload()

try {
    // --- params -------------------------------------------------------------
    $pageUid = $_POST['page_uid'] ?? '';
    $file    = $_FILES['map'] ?? null;

    if (!$pageUid || !$file || $file['error']) {
        throw new RuntimeException('invalid params');
    }

    // --- mime / size check --------------------------------------------------
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
        throw new RuntimeException('unsupported mime');
    }
    if ($file['size'] > 8 * 1024 * 1024) {           // 8 MB limit
        throw new RuntimeException('file too large');
    }

    // --- convert -> JPG + resize to max 3000 px -----------------------------
    $jpegBin = processImage(file_get_contents($file['tmp_name']), 3000); // returns JPG binary

    // --- upload to GCS ------------------------------------------------------
    $path = "upload/{$pageUid}/images/map/facility.jpg";
    $url  = gcsUpload($jpegBin, $path);

    echo json_encode(['ok' => 1, 'url' => $url], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => 0, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}