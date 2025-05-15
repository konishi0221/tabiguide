<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__,2).'/core/bootstrap.php';
require_once dirname(__DIR__,2).'/core/gcs_helper.php';

try {
    $body = json_decode(file_get_contents('php://input'), true);
    $page_uid = $body['page_uid'] ?? '';
    $map_key  = $body['map_key']  ?? '';

    if (!$page_uid || !$map_key || !preg_match('/^[1-3]\.jpg$/', $map_key)) {
        throw new RuntimeException('invalid parameters');
    }

    // GCS delete
    $bucket = getenv('GCS_BUCKET') ?: '';
    if (!$bucket) {
        throw new RuntimeException('GCS_BUCKET env not set');
    }

    $client = new Google\Cloud\Storage\StorageClient(gcsOpts());
    $bucketObj = $client->bucket($bucket);
    $obj = $bucketObj->object("upload/{$page_uid}/images/map/{$map_key}");
    if ($obj->exists()) {
        $obj->delete();
    }

    echo json_encode(['ok'=>1]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>$e->getMessage()]);
}
