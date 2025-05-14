<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(__DIR__) . '/../core/prompt_helper.php';
// ----- shared makeEmbedding -------------------------------------------------
require_once dirname(__DIR__, 2) . '/lib/embedding_util.php';
// /public/dashboard/ai  →  ../../core  = /public/core

$rootCore = dirname(__DIR__, 2) . '/core';

require_once $rootCore . '/token_usage.php';      // addCost(), chargeEmbedding ...

/* ---------- Embedding helper (save.php と共通実装) ---------- */
if (!function_exists('makeEmbedding')) {
    require_once dirname(__DIR__, 3) . '/api/chat/AiClient.php'; // AiClient

    /**
     * text-embedding-3-small で埋め込みを生成し、
     * chargeEmbedding() で課金を記録する。
     */
    function makeEmbedding(string $uid, string $text): string
    {
        $ai  = new AiClient();
        $res = $ai->embeddings('text-embedding-3-small', $text, ['uid' => $uid]);

        if (isset($res['error'])) {
            error_log('[update.php] embed error: '.json_encode($res));
            return '[]';
        }

        // 使用トークン → 料金計上
        $tok = (int)($res['usage']['total_tokens'] ?? 0);
        if ($tok > 0) {
            chargeEmbedding($uid, 'text-embedding-3-small', $tok);
        }
        return json_encode($res['data'][0]['embedding'] ?? [], JSON_UNESCAPED_UNICODE);
    }
}

/* ---------- facility JSON を k=>v ペアに展開 ---------- */
function flatten_pairs(array $src, array &$out, string $prefix = '')
{
    foreach ($src as $k => $v) {
        $key = $prefix === '' ? $k : "{$prefix}.{$k}";
        if (is_array($v)) {
            flatten_pairs($v, $out, $key);
        } elseif ($v !== '') {
            $out[$key] = $v;
        }
    }
}


// POST受け取り
$page_uid = $_POST['page_uid'] ?? ($_GET['page_uid'] ?? '');
$target   = $_POST['last_tab'] ?? ''; // ← 更新対象のカラム名

// 🔐 許可されたカラム一覧
$allowed_targets = [
    'base_data', 'geo_data', 'amenities_data',
    'rule_data', 'location_data', 'services_data',
    'contact_data', 'stay_data',
    'base_notes', 'amenities_notes', 'rule_notes',
    'location_notes', 'appeal_notes', 'others_notes'
];

// ❌ 不正チェック
if (!$page_uid || !in_array($target, $allowed_targets, true)) {
    die('❌ 不正なリクエストです');
}

// 🎯 ターゲットのみに絞って保存
$data = $_POST[$target] ?? [];

// JSONエンコード
$json = json_encode($data, JSON_UNESCAPED_UNICODE);

// DB保存
$stmt = $pdo->prepare("UPDATE facility_ai_data SET {$target} = :json WHERE page_uid = :page_uid");
$stmt->execute([
    ':json' => $json,
    ':page_uid' => $page_uid,
]);


/* ---------- FAQ(question) へ同期 & embedding ---------- */
if (str_ends_with($target, '_data')) {          // *_data 更新時のみ
    // 1) 今回保存した JSON $data をそのまま k=>v に展開
    $prefix = str_replace('_data', '', $target);   // base / amenities / rule ...
    if ($prefix === 'base') $prefix = '';          // base.紹介文 → 紹介文
    $pairs  = [];
    flatten_pairs($data, $pairs, $prefix);

    if ($pairs) {
        $faqSql = 'INSERT INTO question(page_uid, composite_key, question, answer, type, mode, state)
                   VALUES(:uid,:ckey,:q,:a,"facility","guest","reply")
                   ON DUPLICATE KEY UPDATE
                     composite_key = VALUES(composite_key),
                     question      = VALUES(question),
                     answer        = VALUES(answer),
                     state         = "reply",
                     id            = LAST_INSERT_ID(id)';
        $faq = $pdo->prepare($faqSql);

        foreach ($pairs as $key => $val) {

            /* ---- Boolean + note handling ---- */
            if (str_ends_with($key, '.value')) {
                // bool → あります / ありません
                // if ($val === '' || $val === null) {
                //     continue; // 未入力はスキップ
                // }
                // "baseKey" without ".value"
                $baseKey = substr($key, 0, -6);

                // human‑readable question名（prefix と base. を除去）
                $q = ($prefix !== '' && str_starts_with($baseKey, $prefix . '.'))
                       ? substr($baseKey, strlen($prefix) + 1)
                       : $baseKey;

                // bool → あります / ありません
                $bool = in_array($val, ['1', 1, true, 'true'], true);
                $ans  = $bool ? 'あります' : 'ありません';

                // note があれば追記
                $noteKey = $baseKey . '.note';
                if (isset($pairs[$noteKey]) && $pairs[$noteKey] !== '') {
                    $ans .= '。※' . $pairs[$noteKey];
                }
            } elseif (str_ends_with($key, '.note')) {
                // note 単体は .value で処理済みなのでスキップ
                continue;
            } else {
                // 通常スカラー (prefix 切り落とし)
                $q  = ($prefix !== '' && str_starts_with($key, $prefix . '.'))
                        ? substr($key, strlen($prefix) + 1)
                        : $key;
                $ans = $val;
            }

            /* ---- Skip when value is empty ---- */
            if ($ans === '' || $ans === null) {
                continue; // 空データは FAQ 登録しない
            }

            /* 1) FAQ upsert */
            $compositeKey = $page_uid . '_' . $q;   // e.g. 681bef5b55ec6_遊具
            $faq->execute([
                ':uid'  => $page_uid,
                ':ckey' => $compositeKey,
                ':q'    => $q,
                ':a'    => $ans
            ]);

            // MySQL の ON DUPLICATE で変化がなかったら rowCount() は 0
            if ($faq->rowCount() === 0) {
                continue;   // 変更なし → embedding も再計算不要
            }

            /* 2) 対応する question.id を取得 */
            $qid = $pdo->lastInsertId() ?: $pdo->query(
                'SELECT id FROM question WHERE page_uid = ' . $pdo->quote($page_uid) .
                ' AND question = ' . $pdo->quote($q) . ' LIMIT 1'
            )->fetchColumn();

            /* 3) Embedding を生成して question.embedding に保存 */
            try {
                // Q と A を連結して埋め込む（統一フォーマット）
                $vecJson = makeEmbedding($page_uid, $q . "\n" . $ans);
                $pdo->prepare('UPDATE question SET embedding = :emb WHERE id = :qid')
                    ->execute([
                        ':emb' => $vecJson,
                        ':qid' => $qid
                    ]);
            } catch (Throwable $e) {
                error_log('[update.php] embedding failed: ' . $e->getMessage());
            }
        }
    }
}

// 戻る
header("Location: base.php?page_uid={$page_uid}&last_tab={$target}&success=1");
exit;

// DELETE FROM question
// WHERE answer IS NULL
//    OR TRIM(answer) = '';