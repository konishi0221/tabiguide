<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';   // $pdo (ERRMODE_EXCEPTION 推奨)

/* ---------- 共通関数 ---------- */
function generateUid(int $len = 8): string
{
    return substr(bin2hex(random_bytes($len)), 0, $len);
}

/* ---------- POST 受信 ---------- */
$mode           = $_POST['mode']           ?? null;
$facility_uid   = $_POST['facility_uid']   ?? null;
$id             = $_POST['id']             ?? null;
$name           = trim($_POST['name']      ?? '');
$en_name        = urldecode(base64_decode($_POST['en_name'] ?? ''));
$category       = $_POST['category']       ?? null;
$lat            = floatval($_POST['lat']   ?? 35.711892);
$lng            = floatval($_POST['lng']   ?? 139.857269);
$description    = trim($_POST['description'] ?? '');
$en_description = urldecode(base64_decode($_POST['en_description'] ?? ''));
$is_visible     = intval($_POST['is_visible'] ?? 1);
$uid            = $_POST['uid'] ?: generateUid(8);
$url            = urldecode(base64_decode($_POST['url'] ?? ''));

/* ---------- 画像準備 ---------- */
/* 保存先: /workspace/public/upload/{facility_uid}/stores/{uid}.jpg */
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/upload/{$facility_uid}/stores/";
$imagePath = $uploadDir . $uid . '.jpg';
$hasUpload = !empty($_FILES['image']['tmp_name']);

try {
    $pdo->beginTransaction();

    /* 画像アップロード処理 */
    if ($hasUpload) {

        /* ディレクトリ確保 */
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new RuntimeException("mkdir failed: {$uploadDir}");
        }

        /* 画像形式チェック (JPG / PNG のみ) */
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('unsupported image type');
        }

        /* 一時ファイルから移動 */
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            throw new RuntimeException('image move failed');
        }
        chmod($imagePath, 0644);
    }

    /* ---------- DB 保存 ---------- */
    $params = [
        ':name'            => $name,
        ':category'        => $category,
        ':lat'             => $lat,
        ':lng'             => $lng,
        ':description'     => $description,
        ':is_visible'      => $is_visible,
        ':url'             => $url,
        ':uid'             => $uid,
        ':en_name'         => $en_name,
        ':en_description'  => $en_description,
        ':facility_uid'    => $facility_uid
    ];

    if ($mode === 'update' && $id) {
        /* 更新 */
        $sql = "UPDATE stores SET
                  name=:name, category=:category, lat=:lat, lng=:lng,
                  description=:description, is_visible=:is_visible,
                  url=:url, uid=:uid, en_name=:en_name,
                  en_description=:en_description, facility_uid=:facility_uid
                WHERE id=:id";
        $params[':id'] = $id;
    } else {
        /* 新規 */
        $sql = "INSERT INTO stores
                  (name, category, lat, lng, description, is_visible,
                   url, uid, en_name, en_description, facility_uid)
                VALUES
                  (:name, :category, :lat, :lng, :description, :is_visible,
                   :url, :uid, :en_name, :en_description, :facility_uid)";
    }

    $pdo->prepare($sql)->execute($params);
    $pdo->commit();
    header("Location: list.php?success=1&page_uid={$facility_uid}");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($hasUpload && is_file($imagePath)) unlink($imagePath);          // 巻き戻し
    error_log($e->getMessage());
    header("Location: list.php?error=1&page_uid={$facility_uid}");
}
exit;
