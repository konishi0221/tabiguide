<?php
require_once __DIR__ . '/bootstrap.php';
use Google\Cloud\Storage\StorageClient;


/** 共通で使う認証オプションを返す */
function gcsOpts(): array
{
    $opts = [];

    /* ローカル開発 ─ keyFilePath 指定 */
    if ($cred = getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
        if (!file_exists($cred)) {
            throw new RuntimeException("認証ファイルが見つかりません: {$cred}");
        }
        $opts['keyFilePath'] = $cred;
    }
    return $opts;      // 本番は空配列 → メタデータ SA で認証
}

/* ---------- Upload ---------- */
function gcsUpload(string $bin, string $key): string
{
    $bucket = getenv('GCS_BUCKET')
        ?: throw new RuntimeException('GCS_BUCKET 未設定');

    $storage = new StorageClient(gcsOpts());
    $storage->bucket($bucket)->upload($bin, [
        'name'          => $key,
        'predefinedAcl' => 'publicRead',
    ]);
    return "https://storage.googleapis.com/{$bucket}/{$key}";
}

/* ---------- Delete ---------- */
function gcsDelete(string $key): void
{
    $bucket  = getenv('GCS_BUCKET')
        ?: throw new RuntimeException('GCS_BUCKET 未設定');

    $storage = new StorageClient(gcsOpts());
    $object  = $storage->bucket($bucket)->object($key);

    if ($object->exists()) {
        $object->delete();
        error_log("gcsDelete: deleted {$key}");
    } else {
        error_log("gcsDelete: not found {$key}");
    }
}
