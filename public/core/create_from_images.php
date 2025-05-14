<?php
require_once __DIR__ . '/dashboard_head.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/core/token_usage.php';

use OpenAI\Client as OpenAIClient;


function extractAndValidateJson($raw) {
    // 1. JSONっぽい部分だけを抽出（```json〜``` を除去）
    if (preg_match('/```json(.*?)```/s', $raw, $matches)) {
        $jsonString = trim($matches[1]);
    } else {
        $jsonString = trim($raw); // fallback
    }

    // 2. バリデーションして返す
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSONデコードに失敗しました: " . json_last_error_msg());
    }

    return $decoded;
}

function create_from_images(array $encodedImages, string $facility_type, array $template, string $prompt): string {
    global $openai_key;

    $client = OpenAI::client($openai_key);

    $messages = [
        ['role' => 'system', 'content' => 'あなたは施設情報を構造化するAIです。'],
        ['role' => 'user', 'content' => array_merge(
            [['type' => 'text', 'text' => $prompt]],
            $encodedImages
        )],
    ];

    $response = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.3,
    ]);

    // --- cost accounting ---------------------------------
    if (isset($response['usage']['prompt_tokens'])) {
        chargeGPT(
            'system',                       // 施設生成は管理タスク扱い
            'gpt-4o-mini',
            $response['usage']['prompt_tokens']     ?? 0,
            $response['usage']['completion_tokens'] ?? 0
        );
    }
    // ------------------------------------------------------

    $result = $response['choices'][0]['message']['content'] ?? '';
    return json_encode(extractAndValidateJson($result), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}





// function create_from_images($images, $facility_type) {
//   global $openai_key;
//
//   $encodedImages = [];
//
//   foreach ($_FILES['images']['tmp_name'] as $index => $tmpPath) {
//       $client = OpenAI::client($openai_key); // または 直接 $openai_key を使ってもOK
//       if ($index >= 10) break;
//
//       $ext = strtolower(pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION));
//       $mime = mime_content_type($tmpPath);
//
//       $imagick = new Imagick();
//
//       if ($mime === 'application/pdf') {
//           $imagick->setResolution(150, 150);
//           $imagick->readImage($tmpPath);
//           $imagick->setImageFormat('jpeg');
//
//           foreach ($imagick as $i => $page) {
//               if (count($encodedImages) >= 10) break;
//               $page->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1); // 幅600pxに縮小
//               $imageBlob = $page->getImageBlob();
//               $base64 = base64_encode($imageBlob);
//               $encodedImages[] = [
//                   'type' => 'image_url',
//                   'image_url' => [
//                       'url' => 'data:image/jpeg;base64,' . $base64
//                   ]
//               ];
//           }
//
//       } else {
//           $imagick->readImage($tmpPath);
//           $imagick->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
//           $imagick->setImageFormat('jpeg');
//
//           $imageBlob = $imagick->getImageBlob();
//           $base64 = base64_encode($imageBlob);
//           $encodedImages[] = [
//               'type' => 'image_url',
//               'image_url' => [
//                   'url' => 'data:image/jpeg;base64,' . $base64
//               ]
//           ];
//       }
//
//       $imagick->clear();
//       $imagick->destroy();
//   }
//
//
//
//
//
//   // 📦 テンプレート読み込み
//   $templatePath = __DIR__ . '/facility_template.json';
//   $template = json_decode(file_get_contents($templatePath), true);
//   $template['基本情報']['施設タイプ'] = $facility_type;
//
//
//   $noteFields = ['base_notes', 'amenities_notes', 'rule_notes', 'location_notes', 'appeal_notes', 'others_notes'];
//   foreach ($noteFields as $field) {
//       $template[$field] = "";
//   }
//   $template['rooms'] = [];
//
//
//   // 🧠 プロンプト作成
//   $templateJson = json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
//   $prompt = <<<EOT
//   あなたは高精度の構造化AIです。
//   以下の画像群には、宿泊施設の情報が視覚的・文章的に含まれています。
//
//   ### 【目的】
//   画像の中から「宿泊施設の特徴・設備・ルール・周辺情報」などを読み取り、
//   以下のテンプレートに沿って **JSON形式で構造化された出力**をしてください。
//
//   ---
//
//   ## 【出力形式のルール】
//
//   - 出力は JSON 形式とします（テンプレートに準拠）
//   - boolean型の項目は `{ "value": true/false, "note": "補足" }` 形式で記述すること
//     - note はなるべく空欄にせず、判断の根拠・備考を簡潔に記載すること
//     - 判断できない場合は `"value": false` とし、 `"note": "情報が画像に見当たらなかったため"` などの理由を記載すること
//   - 構造化できないが重要な情報がある場合は、該当する `*_notes` に日本語の箇条書き（"・"）形式で記述すること（1行1項目）
//   - `_notes` は意味のある情報をなるべく丁寧に記述してください。装飾的ではなく、実用的かつ正確な内容にしてください。
//   - 所在地（住所）が記載されている場合、緯度・経度が取得可能かを判断し、可能であれば `"緯度": "...", "経度": "..."` を記述してください。
//
//   ---
//
//   ## 【部屋情報（rooms）】
//
//   - 部屋情報が画像または文章から読み取れる場合は `rooms` フィールドにリスト形式で記述してください
//   - 各部屋の要素：
//     - 名称（例："和室1", "Mainroom", "Bedroom A"）
//     - 部屋タイプ（例："寝室", "リビング", "共有スペース" など）
//     - 収容人数（int）
//     - ベッド構成：シングル/ダブル/布団などを `{ "シングルベッド": 1, "ダブルベッド": 0, "布団": 2 }` のように構造化
//     - 設備・アメニティ：各部屋に特化した設備（例：テレビ、エアコン、デスクなど）をbooleanで記述
//     - 備考：特徴や空間の説明を自由記述で
//
//   部屋が特定できない場合や一棟貸しで部屋の構造がない場合は、`rooms` は空配列 `[]` としてください。
//
//   ---
//
//   ## 【各 *_notes フィールドの定義】
//
//   - `base_notes`：施設の基本仕様や構造に関する補足（例：古民家リノベーション、階段あり、一棟貸しなど）
//   - `amenities_notes`：設備や備品、アメニティに関する補足（例：檜風呂、和室に座卓あり、冷蔵庫完備など）
//   - `rule_notes`：宿泊ルールや制限事項、注意点などの補足（例：喫煙違反は罰金あり、騒音制限、清掃はセルフなど）
//   - `location_notes`：立地や周辺環境に関する補足（例：徒歩10分圏内に飲食店多数、静かな住宅街など）
//   - `appeal_notes`：施設の魅力・特徴・雰囲気などの補足（例：日本庭園風のエントランス、和風の照明、木の香り）
//   - `others_notes`：分類できないが重要な情報（例：6歳以下は無料、スタッフ無人対応、ゴミ出し要相談）
//
//   ---
//
//   ### 【テンプレート】
//   {$templateJson}
//   EOT;
//
//   // 💬 メッセージ構築
//   $messages = [
//       [ 'role' => 'system', 'content' => 'あなたは施設情報を構造化するAIです。' ],
//       [ 'role' => 'user', 'content' => array_merge(
//           [['type' => 'text', 'text' => $prompt]],
//           $encodedImages
//       )]
//   ];
//
//   // 🔁 GPT-4o 呼び出し
//   $response = $client->chat()->create([
//       'model' => 'gpt-4o',
//       'messages' => $messages,
//       'temperature' => 0.3,
//   ]);
//
//
//   // 🧾 結果出力（あとでDB保存に切り替える）
//   $result = $response['choices'][0]['message']['content'] ?? 'なし';
//
//   try {
//       $structuredData = extractAndValidateJson($result); // ← $result は OpenAI の返り値
//       return json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
//   } catch (Exception $e) {
//       return "エラー: " . $e->getMessage();
//   }
// }
//
//
//
//
