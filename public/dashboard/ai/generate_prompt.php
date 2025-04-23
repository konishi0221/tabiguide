<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? null;
if (!$page_uid) {
    exit('page_uid が指定されていません');
}

$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? AND user_uid = ?");
$stmt->execute([$page_uid, $_SESSION['user']['uid']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) exit("データが見つかりません");

function safe($json) {
    return json_decode($json ?? '', true) ?? [];
}

$base_json = json_encode($base ?: [], JSON_UNESCAPED_UNICODE);
$amenities_json = json_encode($amenities ?: [], JSON_UNESCAPED_UNICODE);
$rules_json = json_encode($rules ?: [], JSON_UNESCAPED_UNICODE);
$location_json = json_encode($location ?: [], JSON_UNESCAPED_UNICODE);
$baseInfo = $base['基本情報'] ?? [];

$facility_name = $baseInfo['施設名'] ?? 'この施設';
$facility_type = $baseInfo['施設タイプ'] ?? '宿泊施設';

$prompt = <<<EOT
あなたは {$facility_name} という {$facility_type} の受付スタッフです。
{$facility_type} の情報は以下です。
JSONデータの読み方についてですが、例えば "Wi-Fi":{"note":"共有スペースにあります。","value":"0"} となっていた場合、value: 0なので用意はないと思ってください。逆に1の場合はあります。"note"となっている箇所は備考です。
{
  "base_data": {$base_json},
  "amenities_data": {$amenities_json},
  "rule_data": {$rules_json},
  "location_data": {$location_json}
}
以下は
返答のルール
・質問内容が理解できる場合、その質問に対して最適な回答を行ってください。
・{$facility_name} や、周辺地域について以外の全く関係ない質問には答えなくていい。
・長文になると見にくいから「。」のあとは<br>タグで改行して。
・urlを出す時は<a target="_blank">のタグで出力して。
・地域の観光スポットやお店などを聞かれた場合は <a href='/guest/map/'>マップ</a>のリンクを出力して。
・質問内容がわからなかった場合、回答の後に「#」を出力し、わからなかった内容を出力してください。

例えば、質問が「{$facility_name}の向かいには何がある？」だった場合、まず考えて回答し、
その後で「#{$facility_name}の向かいには何があるかがわからなかった」と出力してください。

上記の情報を踏まえて次の質問に答えてください：
EOT;

$updateStmt = $pdo->prepare("UPDATE facility_ai_data SET prompt = ? WHERE page_uid = ? AND user_uid = ?");
$updateStmt->execute([$prompt, $page_uid, $_SESSION['user']['uid']]);

echo "✅ プロンプトを保存しました";
?>
