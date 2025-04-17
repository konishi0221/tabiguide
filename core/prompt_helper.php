<?php
require_once __DIR__ . '/../core/db.php';

function prompt_create($page_uid) {
  global $pdo;

  $stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
  $stmt->execute([$page_uid]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data) return false;

  function safe($json) {
    return json_decode($json ?? '', true);
  }

  // 語尾が _note のカラムを抽出
  $notes = [];
  foreach ($data as $key => $val) {
    if (str_ends_with($key, '_notes') && !empty($val)) {
      $sectionName = str_replace('_notes', '', $key);
      $notes[$sectionName] = $val;
    }
  }

  $base      = safe($data['base_data']);
  $amenities = safe($data['amenities_data']);
  $rules     = safe($data['rule_data']);
  $location  = safe($data['location_data']);
  $services  = safe($data['services_data']);
  $geo       = safe($data['geo_data']);
  $contact   = safe($data['contact_data']);
  $stay      = safe($data['stay_data']);

  $facility_name = $base['施設名'] ?? 'この施設';
  $facility_type = $base['施設タイプ'] ?? '宿泊施設';

  $json = json_encode([
    '基本情報'       => $base ?? [],
    '設備・アメニティ' => $amenities ?? [],
    'ルール・禁止事項' => $rules ?? [],
    '周辺情報'       => $location ?? [],
    'サービス'       => $services ?? [],
    '連絡先'         => $contact ?? [],
    '宿泊情報'       => $stay ?? [],
    '緯度経度'       => $geo ?? []
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

  $prompt = <<<EOT
あなたは「{$facility_name}」という「{$facility_type}」の受付スタッフです。
以下の情報をもとに、ゲストからの質問に丁寧に回答してください。
{$json}

補足情報:
EOT;

  foreach ($notes as $section => $noteText) {
    if (!empty(trim($noteText))) {
      $prompt .= "\n- {$section}：{$noteText}";
    }
  }
  $prompt .= <<<RULES

\n回答ルール：
・質問内容が理解できる場合、その質問に対して最適な回答を行ってください。
・質問内容がわからなかった場合、回答の後に「#」を出力し、わからなかった内容を出力してください。
\n例えば、質問が「{$facility_name}の向かいには何がある？」だった場合、まず考えて回答し、
その後で「#{$facility_name}の向かいには何があるかがわからなかった」と出力してください。
・{$facility_name} や、周辺地域について以外の全く関係ない質問には答えなくていい。
・長文になると見にくいから「。」のあとは<br>タグで改行して。
・urlを出す時は<a target="_blank">のタグで出力して。
・地域の観光スポットやお店などを聞かれた場合は <a href='/guest/map/?page_uid={$page_uid}'>マップ</a> のリンクを出力して。
\nそれでは質問に回答して下さい：
RULES;

  $update = $pdo->prepare("UPDATE facility_ai_data SET prompt = ? WHERE page_uid = ?");
  $update->execute([$prompt, $page_uid]);

  return true;
}
