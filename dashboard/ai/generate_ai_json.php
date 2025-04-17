<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/core/prompt_helper.php';


use OpenAI\Client as OpenAIClient;

// セッション確認
$user_uid = $_SESSION['user']['uid'] ?? null;
if (!$user_uid) {
    header("Location: /login/");
    exit;
}

// URL受け取り
$url = trim($_POST['url'] ?? '');
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    header("Location: /dashboard/index.php?error=invalid_url");
    exit;
}

// テンプレート読み込み
$templatePath = dirname(__DIR__, 2) . '/core/facility_template.json';
$template = json_decode(file_get_contents($templatePath), true);
$templateJson = json_encode($template, JSON_UNESCAPED_UNICODE);

// OpenAI APIキー確認
if (!$openai_key) {
    header("Location: /dashboard/index.php?error=no_openai_key");
    exit;
}

// プロンプト作成
$systemPrompt = <<<EOT
あなたは宿泊施設の情報を構造化するAIです。

以下のURLは宿泊施設の紹介ページです。内容を分析し、提供されている情報から構造化されたJSONを出力してください。
対象は民泊、旅館、ホテル、ビジネスホテル、キャンプ場などすべての宿泊施設が含まれます。

- "施設タイプ" は文字列形式で記載してください（例: "民泊"）。配列にはしないでください。
- 出力はテンプレートに従って、**JSONのみを返してください**（コードブロックや説明文は禁止）

さらに、以下の「補足情報」も出力に含めてください：
- base_notes: 施設の基本情報に関する補足
- amenities_notes: 設備・アメニティに関する補足
- rule_notes: ハウスルール・マナーに関する補足
- location_notes: 周辺地域やアクセスに関する補足
- appeal_notes: 特に伝えたい魅力やおすすめポイント
- others_notes: その他の伝達事項

【テンプレート】
$templateJson

対象URL:
$url
EOT;

// API呼び出し
$client = OpenAI::client($openai_key);
$response = $client->chat()->create([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'あなたは宿泊施設構造化AIです。JSON形式で情報を出力します。'],
        ['role' => 'user', 'content' => $systemPrompt],
    ],
]);

$aiContent = $response['choices'][0]['message']['content'] ?? '';
$parsed = json_decode($aiContent, true);
//
// echo "<h2>OpenAIの返却内容（JSONとして処理前）:</h2>";
// echo "<pre>" . htmlspecialchars($aiContent) . "</pre>";
// exit;


if (!$parsed) {
    header("Location: /dashboard/index.php?error=invalid_json");
    exit;
}

// --- 整形処理 ---
function extractSection($parsed, $label) {
    return isset($parsed[$label]) && is_array($parsed[$label]) ? [$label => normalizeValueNote($parsed[$label])] : [$label => []];
}

// booleanなどを value/note 構造に変換（配下すべてチェック）
function normalizeValueNote($array) {
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $array[$key] = normalizeValueNote($val);
        } else {
            if (is_bool($val) || $val === 0 || $val === 1 || $val === "0" || $val === "1") {
                $array[$key] = [
                    "value" => (int)(bool)$val,
                    "note" => ""
                ];
            }
        }
    }
    return $array;
}

// _notes はキー名そのまま取得
function getNoteLoose($parsed, $targetKey) {
    foreach ($parsed as $key => $val) {
        if (strtolower(trim($key)) === strtolower($targetKey)) {
            return $val;
        }
    }
    return '';
}

// データセクション（_data）
$dataSections = [
    'base_data'      => '基本情報',
    'amenities_data' => '設備・アメニティ',
    'rule_data'      => 'ルール・禁止事項',
    'location_data'  => '周辺情報',
    'services_data'  => 'サービス',
    'contact_data'   => '連絡先',
    'stay_data'      => '宿泊情報',
    'geo_data'       => '緯度経度'
];

// _notes
$noteFields = [
    'base_notes', 'amenities_notes', 'rule_notes',
    'location_notes', 'appeal_notes', 'others_notes'
];

// 施設タイプの整形
if (isset($parsed['基本情報']['施設タイプ']) && is_array($parsed['基本情報']['施設タイプ'])) {
    $parsed['基本情報']['施設タイプ'] = $parsed['基本情報']['施設タイプ'][0] ?? '';
}

// データ格納処理
$page_uid = uniqid();
$nestedData = [];
foreach ($dataSections as $col => $section) {
    $nestedData[$col] = extractSection($parsed, $section);
}

$notes = [];
foreach ($noteFields as $noteKey) {
    $notes[$noteKey] = getNoteLoose($parsed, $noteKey);
}

// 保存処理
$stmt = $pdo->prepare("INSERT INTO facility_ai_data (
    user_uid, page_uid,
    base_data, amenities_data, rule_data, location_data,
    services_data, contact_data, stay_data, geo_data,
    base_notes, amenities_notes, rule_notes, location_notes,
    appeal_notes, others_notes,
    created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$stmt->execute([
    $user_uid,
    $page_uid,
    json_encode($nestedData['base_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['amenities_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['rule_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['location_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['services_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['contact_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['stay_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    json_encode($nestedData['geo_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    $notes['base_notes'],
    $notes['amenities_notes'],
    $notes['rule_notes'],
    $notes['location_notes'],
    $notes['appeal_notes'],
    $notes['others_notes']
]);

// ディレクトリ作成
@mkdir(dirname(__DIR__, 2) . "/upload/{$page_uid}/stores", 0777, true);
@mkdir(dirname(__DIR__, 2) . "/upload/{$page_uid}/images", 0777, true);

prompt_create($page_uid);

// 完了
header("Location: /dashboard/index.php?created=1");
exit;
