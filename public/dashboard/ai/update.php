<?php
// var_dump($_POST['amenities_data']['シャワー']['value'] // "1" または "0"
// );
// exit;
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(__DIR__) . '/../core/prompt_helper.php';

// POST受け取り
$page_uid = $_POST['page_uid'] ?? '';
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

// プロンプト再生成（任意）
prompt_create($page_uid);

// 戻る
header("Location: base.php?page_uid={$page_uid}&last_tab={$target}&success=1");
exit;
