<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$user_uid = $_SESSION['user']['uid'] ?? '';
$page_uid = uniqid();
$facility_name = trim($_POST['facility_name'] ?? '（名称未設定）');
$facility_type = $_POST['facility_type'] ?? '民泊'; // デフォルトは民泊

// テンプレート読み込み
$templatePath = dirname(__DIR__, 2) . '/core/facility_template.json';
$template = json_decode(file_get_contents($templatePath), true);

// 差し替え：施設名・タイプ
$template['施設名'] = $facility_name;
$template['施設タイプ'] = $facility_type;

// アメニティ差し替え（キャンプ場なら）
if ($facility_type === 'キャンプ場') {
    $campPath = dirname(__DIR__, 2) . '/core/camp_amenity_template.json';
    if (file_exists($campPath)) {
        $campAmenity = json_decode(file_get_contents($campPath), true);
        if (isset($campAmenity['設備・アメニティ'])) {
            $template['設備・アメニティ'] = $campAmenity['設備・アメニティ'];
        }
    }
}

// JSONに分割保存
$values = [
    'page_uid'         => $page_uid,
    'user_uid'         => $user_uid,
    'base_data'        => json_encode([
        '施設名'     => $template['施設名'] ?? '',
        '施設タイプ' => $template['施設タイプ'] ?? 'minpaku',
        '所在地'     => $template['所在地'] ?? '',
        '紹介文'     => $template['紹介文'] ?? '',
    ], JSON_UNESCAPED_UNICODE),
    'geo_data'         => json_encode($template['緯度経度'] ?? [], JSON_UNESCAPED_UNICODE),
    'contact_data'     => json_encode($template['連絡先'] ?? [], JSON_UNESCAPED_UNICODE),
    'stay_data'        => json_encode($template['宿泊情報'] ?? [], JSON_UNESCAPED_UNICODE),
    'amenities_data'   => json_encode($template['設備・アメニティ'] ?? [], JSON_UNESCAPED_UNICODE),
    'rule_data'        => json_encode($template['ルール・禁止事項'] ?? [], JSON_UNESCAPED_UNICODE),
    'location_data'    => json_encode($template['周辺情報'] ?? [], JSON_UNESCAPED_UNICODE),
    'services_data'    => json_encode($template['サービス'] ?? [], JSON_UNESCAPED_UNICODE),
    'base_notes'       => $template['base_notes'] ?? '',
    'amenities_notes'  => $template['amenities_notes'] ?? '',
    'rule_notes'       => $template['rule_notes'] ?? '',
    'location_notes'   => $template['location_notes'] ?? '',
    'appeal_notes'     => $template['appeal_notes'] ?? '',
    'others_notes'     => $template['others_notes'] ?? '',
    'created_at'       => date('Y-m-d H:i:s'),
    'updated_at'       => date('Y-m-d H:i:s')
];

// DB挿入
$keys = array_keys($values);
$placeholders = implode(',', array_map(fn($k) => ":$k", $keys));
$sql = "INSERT INTO facility_ai_data (" . implode(',', $keys) . ") VALUES ($placeholders)";
$stmt = $pdo->prepare($sql);
foreach ($values as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->execute();

// 完了 → 編集ページへリダイレクト
header("Location: /dashboard/ai/base.php?page_uid=" . urlencode($page_uid));
exit;
