<?php
require_once __DIR__ . '/../../core/dashboard_head.php';
require_once __DIR__ . '/../../core/create_from_images.php';
require_once __DIR__ . '/../../core/image_helper.php'; // 👈 新しいファイルを追加
require_once __DIR__ . '/../../core/facility_template.php' ; // ← PHPから読み込む


$page_uid = $_POST['page_uid'] ?? null;
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('POSTメソッドでアクセスしてください');
// if (!$page_uid) die('page_uidが指定されていません');
// if (!isset($_POST['facility_type']) || empty($_FILES['images'])) die('施設タイプまたは画像が未指定です');

$facility_type = $_POST['facility_type'];
$files = $_FILES['images'];


$template = loadFacilityTemplate($facility_type);
// var_dump()

echo '<pre>' . htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
exit;


// 🖼️ エンコード済み画像を作成
$encodedImages = resize_and_encode_images($files);

// // 📦 テンプレとプロンプト読み込み
// $templatePath = __DIR__ . '/../../core/facility_template.json';
// $template = json_decode(file_get_contents($templatePath), true);
// $template['施設タイプ'] = $facility_type;

$noteFields = ['base_notes', 'amenities_notes', 'rule_notes', 'location_notes', 'appeal_notes', 'others_notes'];
foreach ($noteFields as $field) {
    $template[$field] = '';
}
$template['rooms'] = [];

$templateJson = json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);



$noteFields = ['base_notes', 'amenities_notes', 'rule_notes', 'location_notes', 'appeal_notes', 'others_notes'];
foreach ($noteFields as $field) {
    $template[$field] = '';
}
$template['rooms'] = [];

$templateJson = json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$prompt = <<<EOT
あなたは高精度の構造化AIです。
以下の画像群には、宿泊施設の情報が視覚的・文章的に含まれています。

---

### 【目的】

この宿泊施設に関する **すべての情報**（基本情報、設備、ルール、サービス、立地、魅力など）を画像から抽出し、
以下のテンプレートに沿って **JSON形式で構造化**してください。

❗ **注意**：
このステップでは「部屋構成」の情報（例：和室の数、洋室の数、リビングやダイニングの有無など）は構造化してください。
ただし、個別の部屋の詳細（部屋名・ベッド構成・アメニティなど）は出力しないでください。
テンプレート内に `rooms: []` が存在していても、その内容は空配列のままで構いません。
部屋情報の構造化は次のステップで実施されます。
基本情報の施設タイプは絶対に変更しないでください。

---

### 【出力形式のルール】

- 出力は JSON 形式とします（テンプレートに準拠）
- boolean型の項目は `{ "value": true/false, "note": "補足" }` 形式で記述してください
  - `note` はなるべく空欄にせず、判断の根拠・備考を簡潔に記載してください
  - 判断できない場合は `"value": false` とし、 `"note": "情報が画像に見当たらなかったため"` などの理由を明記してください
- 構造化できないが重要な情報がある場合は、該当する `*_notes` に日本語の箇条書き（"・"）形式で記述してください（1行1項目）
- `_notes` は装飾的な文章ではなく、**実用的かつ具体的な補足情報**を丁寧に記述してください
- 所在地（住所）が確認できる場合、緯度・経度が取得可能かを判断し、可能であれば `"緯度": "...", "経度": "..."` を含めてください
- `"施設タイプ"` は必ず文字列（例：`"旅館"` や `"ホテル"` など）として出力してください（配列不可）

---

### 【部屋情報（rooms）】

このステップでは部屋情報を構造化しないでください。
テンプレートに `rooms: []` が含まれていても、そのまま空配列で出力してください。
部屋情報は後続ステップで処理されます。

---

### 【各 *_notes フィールドの定義】

- `base_notes`：施設の構造や基本仕様に関する補足（例：古民家リノベーション、階段あり、一棟貸し等）
- `amenities_notes`：全体設備や備品・アメニティに関する補足（例：檜風呂、和室に座卓あり、冷蔵庫完備等）
- `rule_notes`：宿泊ルールや制限・注意事項などの補足（例：喫煙違反は罰金あり、ペット禁止、夜間静粛等）
- `location_notes`：立地や周辺環境に関する補足（例：閑静な住宅街、飲食店密集エリア、駅徒歩5分等）
- `appeal_notes`：施設の魅力や独自性・雰囲気など（例：日本庭園、アート装飾、デザイナーズ建築等）
- `others_notes`：上記に分類できないが重要な補足（例：6歳以下無料、チェックアウト後荷物預かり可等）

---

### 【配列の中にオブジェクトを含む項目について】

- 一部の項目（例："アクティビティ", "サービス詳細", "貸出備品" など）は、配列の中にオブジェクト形式で情報を並べることがあります。
- そのような項目では、各オブジェクトに `"title"`, `"content"`, `"price"` のようなキーを含めてください。
- 内容が単純なリストではなく、タイトルと説明・金額などを伴う情報であると判断される場合は、
  それぞれをオブジェクトとして配列に追加し、並列で記述してください。
- 情報が1件しかない場合でも、配列形式で出力してください。


### 【テンプレート】

{$templateJson}
EOT;

// 🔁 AI構造化JSONを取得
$json = create_from_images($encodedImages, $facility_type, $template, $prompt);

// 📝 以降の処理はそのまま（$data にして DBへ保存処理）...

?>




<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>施設情報の確認</title>
  <style>
    body {
      font-family: monospace;
      background: #f9f9f9;
      padding: 2rem;
    }
    pre {
      background: #fff;
      border: 1px solid #ccc;
      padding: 1rem;
      overflow-x: auto;
      white-space: pre-wrap;
      word-break: break-all;
    }
    h1 {
      font-size: 1.5em;
      margin-bottom: 1rem;
    }
    .button {
      display: inline-block;
      background: #007acc;
      color: #fff;
      padding: 0.6em 1.2em;
      border-radius: 4px;
      text-decoration: none;
      margin-top: 1em;
    }
  </style>
</head>
<body>
  <h1>構造化された施設情報の確認</h1>
  <pre><?= htmlspecialchars($json, ENT_QUOTES, 'UTF-8') ?></pre>
  <a class="button" href="index.php?page_uid=<?= htmlspecialchars($page_uid) ?>">戻る</a>
</body>
</html>
