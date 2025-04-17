<?php
// guest_head.php

require_once __DIR__ . '/db.php';           // ← core/db.php を読み込むならこれ
require_once __DIR__ . '/config.php';       // ← configも同様に
require_once __DIR__ . '/load_design.php';

// page_uid がない場合はエラーを返す
$page_uid = $_GET['page_uid'] ?? null;
if (!$page_uid) {
    echo "施設UIDが指定されていません。";
    exit;
}

// 必要なら、施設情報を取得して $facility_data とかで共通化も可能
$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = :page_uid LIMIT 1");
$stmt->execute([':page_uid' => $page_uid]);
$facility = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facility) {
    echo "指定された施設が見つかりません。";
    exit;
}


$design = [
    'primary_color'           => '#000000',
    'secondary_color'         => '#6b6b6b',
    'background_color'        => '#ffffff',
    'text_color'              => '#333333',
    'chat_bubble_color_user'  => '#6b6b6b',
    'chat_bubble_color_ai'    => '#eeeeee',
    'font_family'             => 'system-ui, sans-serif',
    'button_radius'           => 4,
    'dark_mode'               => 0,
    'logo_base64'             => null,
    'chat_icon_base64'        => null,
    'map_icon_base64'         => null,
    'navbar_style'            => '',
    'css_override'            => '',
    'map_style_json'          => '',
];



$stmt = $pdo->prepare("SELECT * FROM design WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$db_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($db_data) {
    $design = array_merge($design, $db_data);
}
