<?php
require_once __DIR__ . '/../core/db.php';

$page_uid = $_GET['page_uid'] ?? '';

if (!$page_uid) {
    echo "❌ page_uid を指定してください。例: <code>?page_uid=page_xxxxxx</code>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ?");
$stmt->execute([$page_uid]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "❌ データが見つかりません。";
    exit;
}

function renderJsonBlock($label, $json) {
    $decoded = json_decode($json, true);
    if (!$decoded) return "<p><strong>{$label}:</strong> <span style='color:gray;'>（無効または空のJSON）</span></p>";
    return "<details open><summary><strong>{$label}</strong></summary><pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre></details>";
}

function renderTextBlock($label, $text) {
    $clean = htmlspecialchars($text ?: '（なし）');
    return "<p><strong>{$label}:</strong><br><span style='white-space: pre-line;'>{$clean}</span></p>";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>施設データ詳細</title>
  <style>
    body { font-family: sans-serif; padding: 2em; background: #f9f9f9; }
    .container { background: white; padding: 2em; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1 { margin-top: 0; }
    pre { background: #f0f0f0; padding: 1em; border-radius: 4px; overflow-x: auto; }
    summary { cursor: pointer; font-weight: bold; margin-bottom: 0.5em; }
    details { margin-bottom: 1.5em; }
  </style>
</head>
<body>
<div class="container">
  <h1>施設データ詳細</h1>
  <p><strong>page_uid:</strong> <?= htmlspecialchars($data['page_uid']) ?></p>

  <?= renderJsonBlock('基本情報 (base_data)', $data['base_data']) ?>
  <?= renderJsonBlock('アメニティ (amenities_data)', $data['amenities_data']) ?>
  <?= renderJsonBlock('ルール (rule_data)', $data['rule_data']) ?>
  <?= renderJsonBlock('周辺情報 (location_data)', $data['location_data']) ?>
  <?= renderJsonBlock('サービス (services_data)', $data['services_data']) ?>
  <?= renderJsonBlock('連絡先 (contact_data)', $data['contact_data']) ?>
  <?= renderJsonBlock('宿泊情報 (stay_data)', $data['stay_data']) ?>
  <?= renderJsonBlock('緯度経度 (geo_data)', $data['geo_data']) ?>

  <hr>

  <?= renderTextBlock('設備補足 (base_notes)', $data['base_notes']) ?>
  <?= renderTextBlock('アメニティ補足 (amenities_notes)', $data['amenities_notes']) ?>
  <?= renderTextBlock('ルール補足 (rule_notes)', $data['rule_notes']) ?>
  <?= renderTextBlock('周辺情報補足 (location_notes)', $data['location_notes']) ?>
  <?= renderTextBlock('アピールポイント (appeal_notes)', $data['appeal_notes']) ?>
  <?= renderTextBlock('その他 (others_notes)', $data['others_notes']) ?>
</div>
</body>
</html>
