<?php

// ✅ テンプレートを読み込んで返す関数
function loadFacilityTemplate(string $facility_type = 'minpaku'): array
{
    $basePath = __DIR__ . '/json';
    $commonPath = $basePath . '/template_common.json';
    $typePath = $basePath . "/template_{$facility_type}.json";

    // 共通テンプレート
    $common = file_exists($commonPath) ? json_decode(file_get_contents($commonPath), true) : [];
    if (!is_array($common)) $common = [];

    // タイプ別テンプレート
    $typeSpecific = file_exists($typePath) ? json_decode(file_get_contents($typePath), true) : [];
    if (!is_array($typeSpecific)) $typeSpecific = [];

    return array_merge($common, $typeSpecific);
}

// ✅ APIとして直接アクセスされた場合：JSONを返す
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $facility_type = $_GET['type'] ?? 'minpaku';

    $template = loadFacilityTemplate($facility_type);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
