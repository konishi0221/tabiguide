<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM design WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults = [
  'primary_color' => '#000000',
  'secondary_color' => '#6b6b6b',
  'background_color' => '#f5f5f5',
  'text_color' => '#333333',
  'font_family' => 'system-ui, sans-serif',
  'button_radius' => 4,
  'chat_bubble_color_user' => '#e0e0e0',
  'chat_bubble_color_ai' => '#ffffff',
  'dark_mode' => 0
];


$design = array_merge($defaults, $design ?: []);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>デザイン設定</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&family=M+PLUS+Rounded+1c&family=Roboto&family=Zen+Kaku+Gothic+New&family=Shippori+Mincho+B1&family=Kosugi+Maru&family=BIZ+UDPGothic&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <style>
    input[type=color], input[type=text], select, textarea {
      margin-bottom: 1em;
      width: 100%;
      max-width: 400px;
    }
  </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

<div class="dashboard-container">

<?php include('../components/side_navi.php'); ?>
  <div id="app" class="container">
    <main>
  <h1>デザイン設定</h1>

  <form method="post" action="save.php" enctype="multipart/form-data">
    <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

    <div class="design-section">
      <label>テーマカラー</label>
      <input type="color" name="primary_color" value="<?= !empty($design['primary_color']) ? $design['primary_color'] : $defaults['primary_color'] ?>">

      <label>アクセントカラー</label>
      <input type="color" name="secondary_color" value="<?= !empty($design['secondary_color']) ? $design['secondary_color'] : $defaults['secondary_color'] ?>">

      <label>背景色</label>
      <input type="color" name="background_color" value="<?= !empty($design['background_color']) ? $design['background_color'] : $defaults['background_color'] ?>">
    </div>

    <div class="design-section">
      <label>テキスト色</label>
      <input type="color" name="text_color" value="<?= !empty($design['text_color']) ? $design['text_color'] : $defaults['text_color'] ?>">

      <label>ユーザー吹き出し色</label>
      <input type="color" name="chat_bubble_color_user" value="<?= !empty($design['chat_bubble_color_user']) ? $design['chat_bubble_color_user'] : $defaults['chat_bubble_color_user'] ?>">

      <label>AI吹き出し色</label>
      <input type="color" name="chat_bubble_color_ai" value="<?= !empty($design['chat_bubble_color_ai']) ? $design['chat_bubble_color_ai'] : $defaults['chat_bubble_color_ai'] ?>">
    </div>

    <div class="design-section">
      <?php
      $font_options = [
        'system-ui, sans-serif' => '標準（system-ui）',
        '"Noto Sans JP", sans-serif' => 'Noto Sans JP（読みやすくモダン）',
        '"M PLUS Rounded 1c", sans-serif' => 'M PLUS Rounded（丸み・柔らか）',
        '"Roboto", sans-serif' => 'Roboto（定番サンセリフ）',
        '"Zen Kaku Gothic New", sans-serif' => 'Zen Kaku Gothic（和文ゴシック）',
        '"Shippori Mincho B1", serif' => 'Shippori Mincho（明朝体）',
        '"Kosugi Maru", sans-serif' => 'Kosugi Maru（可愛くて丸い）',
        '"BIZ UDPGothic", sans-serif' => 'BIZ UDPゴシック（企業向け）',
      ];
      $current_font = !empty($design['font_family']) ? $design['font_family'] : $defaults['font_family'];
      ?>
    </div>
    <div class="design-section">

      <label>フォント</label><br>
      <?php foreach ($font_options as $value => $label): ?>
        <label style="font-family: <?= htmlspecialchars($value) ?> !important; display: block; margin-bottom: 5px;">
          <input type="radio" name="font_family" value="<?= htmlspecialchars($value) ?>"
            <?= $current_font === $value ? 'checked' : '' ?>>
          <?= htmlspecialchars($label) ?>
        </label>
      <?php endforeach; ?>

      <label>ボタン角丸(px)</label>
      <input type="number" name="button_radius" value="<?= isset($design['button_radius']) ? $design['button_radius'] : $defaults['button_radius'] ?>" min="0">

      <label>ダークモード</label>
      <select name="dark_mode">
        <option value="0" <?= empty($design['dark_mode']) ? 'selected' : '' ?>>オフ</option>
        <option value="1" <?= !empty($design['dark_mode']) ? 'selected' : '' ?>>オン</option>
      </select>
    </div>

    <?php if (!empty($design['logo_base64'])): ?>
      <div style="margin-bottom:10px;">
        <label>現在のロゴ画像：</label><br>
        <img src="data:image/png;base64,<?= $design['logo_base64'] ?>" width="50" height="50" style="border:1px solid #ccc; border-radius:8px;">
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_logo'): ?>
      <div style="color:red; margin-bottom: 1em;">
        ロゴ画像は PNG / JPG / GIF 形式の画像にしてください。
      </div>
    <?php endif; ?>


    <div class="design-section">
      <label>ロゴ画像（50x50 PNG）</label>
      <input type="file" name="logo_image">
    </div>

    <button type="submit">保存</button>
  </form>
</main>
</div>
</div>
</body>
</html>

<style>
.design-section {
  margin-bottom: 2em;
}
.design-section label {
  display: block;
  font-weight: bold;
  margin-top: 0.5em;
}
.design-section input[type="text"],
.design-section input[type="number"],
.design-section input[type="color"],
.design-section select {
  width: 300px;
  padding: 6px;
  margin-bottom: 0.5em;
}
.design-section input[type="file"] {
  margin-top: 0.3em;
}

.design-section input[type="color"]{
  height: 50px;
  width: 60px
}
button {
  padding: 8px 16px;
  font-weight: bold;
  background-color: #222;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>
