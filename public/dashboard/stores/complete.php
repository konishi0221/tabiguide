<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../core/dashboard_head.php';   // $pdo
require_once dirname(__DIR__) . '/../core/gcs_helper.php';       // gcsUpload()
require_once dirname(__DIR__) . '/../core/functions.php';       // gcsUpload()
require_once dirname(__DIR__) . '/../core/category.php';        // $category_list
require_once dirname(__DIR__) . '/../lib/embedding_util.php';   // makeEmbedding()
function generateUid(int $len = 8): string {
    return substr(bin2hex(random_bytes($len)), 0, $len);
}

$mode         = $_POST['mode']           ?? null;
// POST が無ければ GET から。page_uid パラメータでも受け付ける
$facility_uid = $_POST['facility_uid']
             ?? ($_GET['facility_uid']  ?? ($_GET['page_uid'] ?? null));
$id           = $_POST['id']             ?? null;
$name         = trim($_POST['name']      ?? '');
$en_name      = urldecode(base64_decode($_POST['en_name'] ?? ''));
$category     = $_POST['category']       ?? null;
$lat          = floatval($_POST['lat']   ?? 35.711892);
$lng          = floatval($_POST['lng']   ?? 139.857269);
$description  = trim($_POST['description'] ?? '');
$en_desc      = urldecode(base64_decode($_POST['en_description'] ?? ''));
$is_visible   = intval($_POST['is_visible'] ?? 1);
$uid          = $_POST['uid'] ?: generateUid(8);
// composite_key = page_uid + '_' + stores.uid
$compositeKey = $facility_uid . '_' . $uid;
// --- debug ---
error_log('[stores/complete.php] BEGIN mode=' . ($mode ?? 'null')
        . ' facility_uid=' . ($facility_uid ?? 'null')
        . ' id=' . ($id ?? 'null')
        . ' uid=' . $uid
        . ' POST=' . json_encode($_POST, JSON_UNESCAPED_UNICODE)
        . ' GET='  . json_encode($_GET,  JSON_UNESCAPED_UNICODE));
// -------------
$hasUpload = !empty($_FILES['image']['tmp_name']);

$url          = urldecode(base64_decode($_POST['url'] ?? ''));

try {
    $pdo->beginTransaction();

    if ($hasUpload) {
        // 生ファイル取得
        $file = $_FILES['image']['tmp_name'];
        $bin  = file_get_contents($file);
        if ($bin === false) {
            throw new RuntimeException('failed to read upload');
        }
    
        $maxWidth = 1000;
        $processed = processImage($bin, $maxWidth, $mode);
    
        // ③ PNG化したバイナリを GCS にアップロード
        $objectKey = "stores/{$facility_uid}/{$uid}.png";
        gcsUpload($processed, $objectKey);
    }

    $params = [
        ':name'           => $name,
        ':category'       => $category,
        ':lat'            => $lat,
        ':lng'            => $lng,
        ':description'    => $description,
        ':is_visible'     => $is_visible,
        ':url'            => $url,
        ':uid'            => $uid,
        ':en_name'        => $en_name,
        ':en_description' => $en_desc,
        ':facility_uid'   => $facility_uid,
    ];

    if ($mode === 'update' && $id) {
        $sql = "UPDATE stores SET
                  name           = :name,
                  category       = :category,
                  lat            = :lat,
                  lng            = :lng,
                  description    = :description,
                  is_visible     = :is_visible,
                  url            = :url,
                  uid            = :uid,
                  en_name        = :en_name,
                  en_description = :en_description,
                  facility_uid   = :facility_uid
                WHERE id = :id";
        $params[':id'] = $id;
    } else {
        $sql = "INSERT INTO stores
                  (name,category,lat,lng,description,is_visible,url,uid,en_name,en_description,facility_uid)
                VALUES
                  (:name,:category,:lat,:lng,:description,:is_visible,:url,:uid,:en_name,:en_description,:facility_uid)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    /* ---------- FAQ upsert & embedding for AI search ---------- */
    try {
        // ---- build question title (カテゴリ + 名称) and answer (説明 + Gmap) ----
        $catLabel      = $category_list[$category]['name'] ?? $category;
        $questionTitle = trim($catLabel . ' ' . $name);
        $mapLink       = "https://www.google.com/maps?q={$lat},{$lng}";
        // description を同一行に続け、リンクは改行で分ける
        $answerFull    = trim($description) . "\n" . $mapLink;

        // FAQ upsert (positional placeholders to avoid HY093)
        $faqSql = 'INSERT INTO question
                     (page_uid, composite_key, question, answer, type, mode, state)
                   VALUES
                     (?,?,?,?, "facility","guest","reply")
                   ON DUPLICATE KEY UPDATE
                     composite_key = VALUES(composite_key),
                     question      = VALUES(question),
                     answer        = VALUES(answer),
                     state         = "reply",
                     id            = LAST_INSERT_ID(id)';

        $faq = $pdo->prepare($faqSql);
        $faq->execute([$facility_uid, $compositeKey, $questionTitle, $answerFull]);

        // 2‑2. question.id を取得（composite_key で一意に引く）
        $qid = $pdo->lastInsertId() ?: $pdo->query(
            'SELECT id FROM question WHERE composite_key = ' . $pdo->quote($compositeKey) . ' LIMIT 1'
        )->fetchColumn();

        // 2‑3. embedding を生成して保存
        $content = "Q: " . $questionTitle . "\nA: " . $answerFull;
        $vecJson = makeEmbedding($facility_uid, $content);

        // error_log($vecJson);
        // 2‑4. save embedding JSON into the question row
        $embStmt = $pdo->prepare(
            'UPDATE question SET embedding = :vec WHERE id = :id'
        );
        $embStmt->execute([
            ':vec' => $vecJson,
            ':id'  => $qid,
        ]);
    } catch (Throwable $e) {
        error_log('[stores/complete.php] embedding failed: ' . $e->getMessage());
    }
    /* ---------------------------------------------------------- */

    $pdo->commit();

    header("Location: list.php?success=1&page_uid={$facility_uid}");
    exit;
} catch (Throwable $e) {
    error_log('[stores/complete.php] ERROR ' . $e->getMessage());
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[stores/complete.php] request failed, facility_uid=' . ($facility_uid ?? 'null'));
    header("Location: list.php?error=1&page_uid={$facility_uid}");
    exit;
}
