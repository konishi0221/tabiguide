<?php
// delete_store.php
// Composer autoload
require_once __DIR__ . '/../../../vendor/autoload.php';
// coreファイル読み込み（public/coreフォルダ内）
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';
require_once dirname(__DIR__, 2) . '/core/gcs_helper.php';

$store_id     = $_POST['id']           ?? null;
$uid          = $_POST['uid']          ?? null;
$facility_uid = $_POST['facility_uid'] ?? null;
$mode         = $_POST['mode']         ?? 'delete_store'; // delete_store / delete_image

// GCS オブジェクトキー (.png 拡張子)
$objectKey = "stores/{$facility_uid}/{$uid}.png";


error_log("DELETE key={$objectKey}");

/* 画像だけ削除 */
if ($mode === 'delete_image' && $facility_uid && $uid) {
    try {
        gcsDelete($objectKey);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
    // 編集画面に戻す
    header("Location: index.php?id={$store_id}&page_uid={$facility_uid}&image_deleted=1");
    exit;
}

/* レコード＋画像削除 */
if ($mode === 'delete_store' && $store_id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM stores WHERE id = :id');
        $stmt->execute([':id' => $store_id]);
        // 追加: 関連する FAQ(question) 行を削除
        $ckey = "{$facility_uid}_{$uid}";
        $pdo->prepare('DELETE FROM question WHERE composite_key = :ck')
            ->execute([':ck' => $ckey]);
        // GCSから画像削除
        gcsDelete($objectKey);
        // 一覧に戻る
        header("Location: list.php?page_uid={$facility_uid}&deleted=1");
        exit;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        header("Location: list.php?page_uid={$facility_uid}&error=1");
        exit;
    }
}
