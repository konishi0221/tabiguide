<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? AND user_uid = ? LIMIT 1");
$stmt->execute([$page_uid, $user_uid]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// var_dump($data); exit;

$notes_data = [
    'appeal_notes'    => $data['appeal_notes'] ?? '',
    'base_notes'      => $data['base_notes'] ?? '',
    'amenities_notes' => $data['amenities_notes'] ?? '',
    'rule_notes'      => $data['rule_notes'] ?? '',
    'location_notes'  => $data['location_notes'] ?? '',
    'others_notes'    => $data['others_notes'] ?? '',
];

$notes_labels = [
    'appeal_notes'    => 'アピールポイント',
    'base_notes'      => '設備情報について',
    'amenities_notes' => 'アメニティについて',
    'rule_notes'      => '施設のルールについて',
    'location_notes'  => '近隣・街の情報について',
    'others_notes'    => 'その他の情報'
];

$placeholders = [
    'appeal_notes' => "・部屋から富士山が見える絶景ポイントがあります。\n・露天風呂付きで贅沢な時間をお楽しみいただけます。\n・デザイナーによる内装で写真映えする空間になっています。",
    'base_notes' => "・洗濯機はバルコニーに設置されています。\n・浴室に乾燥機能付き換気扇があります。\n・お湯は電気式給湯器で自動沸かし直し対応です。",
    'amenities_notes' => "・バスタオル・フェイスタオルは各2枚ずつご用意しています。\n・シャンプー・コンディショナー・ボディソープは備え付けです。\n・歯ブラシやカミソリなどはございませんのでご持参ください。",
    'rule_notes' => "・目の前の道路でタバコを吸わないでください。\n・21時以降は近隣住民のため静かにお過ごしください。\n・ペットを室内に入れることは禁止されています。",
    'location_notes' => "・徒歩1分圏内にセブンイレブンとファミリーマートがあります。\n・日中は近隣に工事がある可能性があります。\n・近くの川沿いは朝の散歩におすすめのコースです。",
    'others_notes' => "・お困りごとがあればお気軽にご連絡ください。\n・チェックイン前に荷物を預けたい場合はご相談ください。\n・その他、気になることは事前にご確認いただくと安心です。",
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>追記情報</title>
  <meta name="robots" content="noindex, nofollow">
  <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
</head>

<style>
.setting-section {
  background: #fff;
  padding: 1.5em;
  margin-bottom: 2em;
  border: 1px solid #ddd;
  border-radius: 8px;
}
</style>


<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">

<?php include('../components/side_navi.php'); ?>

<div id="app">
<main>
  <h1>追記情報</h1>

  <?php foreach ($notes_labels as $key => $label): ?>
  <section class="setting-section">
    <form class="block" method="post" action="ps_update.php">
      <input type="hidden" name="field" value="<?= $key ?>">
      <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">
      <label><?= htmlspecialchars($label) ?></label><br>
      <textarea
        name="<?= $key ?>"
        placeholder="<?= htmlspecialchars($placeholders[$key] ?? '') ?>"
        rows="6"
      ><?= htmlspecialchars($notes_data[$key] ?? '') ?></textarea><br>
      <button type="submit">保存</button>
    </form>
  </section>
  <?php endforeach; ?>

  <?php
  $guest_password = $data['guest_password'] ?? '';
  $private_info_notes = $data['private_info_notes'] ?? '';
  ?>

  <section class="setting-section">
  <form class="block" method="post" action="save_password_notes.php">
    <input type="hidden" name="field" value="guest_password">
    <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

    <label>ゲスト用パスワード※定期的に変えてください。</label><br>
    <input type="text" name="guest_password" value="<?= htmlspecialchars($guest_password) ?>" placeholder="1234">

    <label>パスワード入力者にのみ伝える情報</label><br>
    <textarea
      name="private_info_notes"
      placeholder="・WIFI
　ID: ABC_hotel
　PASS: 12345"
      rows="6"
    ><?= htmlspecialchars($private_info_notes) ?></textarea><br>

    <button type="submit">保存</button>
  </form>
</section>


</main>
</div>
</div>

</body>
</html>

<style>
label {
  display: block;
  width: 100%;
}
</style>
