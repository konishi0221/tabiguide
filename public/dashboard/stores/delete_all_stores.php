<?php
// ── delete_all_stores.php ──
require_once __DIR__ . '/../../../vendor/autoload.php';          // Composer
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';   // $pdo
require_once dirname(__DIR__, 2) . '/core/gcs_helper.php';       // gcsDelete()

// 1. page_uid 受け取り
$page_uid = $_POST['page_uid'] ?? null;
if (!$page_uid) {
    echo "ページUIDが指定されていません。";
    exit;
}

// 2. 対象店舗の uid 一覧を取得
$stmt   = $pdo->prepare('SELECT uid FROM stores WHERE facility_uid = ?');
$stmt->execute([$page_uid]);
$uids   = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 3. DB から店舗レコードを一括削除
$pdo->prepare('DELETE FROM stores WHERE facility_uid = ?')->execute([$page_uid]);

// 4. GCS から画像削除（.png と .jpg の両方を試す）
foreach ($uids as $uid) {
    foreach (['png', 'jpg'] as $ext) {
        gcsDelete("stores/{$page_uid}/{$uid}.{$ext}");
    }
}

// 5. 完了 → 一覧へリダイレクト
header('Location: list.php?page_uid=' . urlencode($page_uid) . '&deleted_all=1');
exit;
