<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/core/gcs_helper.php';

try {
    // ---------------------------------------------------------------------
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $pageUid  = $body['page_uid'] ?? '';

    if (!$pageUid) {
        throw new RuntimeException('page_uid required');
    }

    // ---------------------------------------------------------------------
    $bucket = getenv('GCS_BUCKET') ?: '';
    if (!$bucket) {
        throw new RuntimeException('GCS_BUCKET env not set');
    }

    $storage = new Google\Cloud\Storage\StorageClient(gcsOpts());
    $object  = $storage
        ->bucket($bucket)
        ->object("upload/{$pageUid}/images/map/facility.jpg");

    if ($object->exists()) {
        $object->delete();
    }

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => 0, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}