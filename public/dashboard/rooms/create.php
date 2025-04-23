<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$room_uid = $_GET['room_uid'] ?? null;

global $facility_type;
$roomLabel = htmlspecialchars(getRoomLabel($facility_type));

$isEdit = !empty($room_uid);
$room = [
    'room_name' => '',
    'room_type' => '',
    'capacity' => '',
    'notes' => '',
    'amenities_data' => '{}'
];

// JSONテンプレートの読み込み（施設タイプごとに切り替え）
$templatePath = __DIR__ . "/../../core/json/room/{$facility_type}.json";
$templateJson = file_exists($templatePath) ? file_get_contents($templatePath) : '{}';
$room_template = json_decode($templateJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("テンプレートJSONが不正です：" . json_last_error_msg());
}

// 編集時はDBから取得
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_uid = ? AND page_uid = ? LIMIT 1");
    $stmt->execute([$room_uid, $page_uid]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) die("指定された部屋が見つかりません");
}

// アメニティ情報
$amenities_data = json_decode($room['amenities_data'] ?? '{}', true);

  $raw_amenities = json_decode($room['amenities_data'] ?? '{}', true);
$amenities_data = [];

foreach ($room_template['客室設備・アメニティ'] ?? [] as $key => $schema) {
    $amenities_data[$key] = $raw_amenities[$key] ?? ['value' => false, 'note' => ''];
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= $roomLabel ?><?= $isEdit ? '編集' : '作成' ?></title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

  <div id="app">
    <main>
      <h1><?= $roomLabel ?><?= $isEdit ? '編集' : '作成' ?></h1>
      <form method="post" action="complete.php" @submit.prevent="handleSubmit">
        <input type="hidden" name="mode" value="<?= $isEdit ? 'update' : 'insert' ?>">
        <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="room_uid" value="<?= htmlspecialchars($room['room_uid']) ?>">
        <?php endif; ?>

        <label><?= $roomLabel ?>名</label><br>
        <input type="text" name="room_name" value="<?= htmlspecialchars($room['room_name']) ?>" required><br><br>

        <label><?= $roomLabel ?>タイプ</label><br>
        <input type="text" name="room_type" value="<?= htmlspecialchars($room['room_type']) ?>"><br><br>

        <label>定員（人数）</label><br>
        <input type="number" name="capacity" value="<?= htmlspecialchars($room['capacity']) ?>" min="0"><br><br>

        <label>補足情報</label><br>
        <textarea name="notes" rows="5"><?= htmlspecialchars($room['notes']) ?></textarea><br><br>

        <hr>

        <textarea name="amenities_data_json" :value="JSON.stringify(form)" hidden></textarea>
        <!-- <input type="hidden" name="amenities_data_json" :value="JSON.stringify(form)"> -->

        <h2>設備・アメニティ</h2>
        <div v-if="template['客室設備・アメニティ']">
          <component-input
            v-for="(schema, key) in template['客室設備・アメニティ']"
            :key="key"
            :label="key"
            :name="'amenities_data[' + key + ']'"
            :schema="schema"
            :model_value="form[key]"
            :placeholder="schema.placeholder || ''"
            @update:model_value="val => form[key] = val"
          />
        </div>


        <br><br>
        <button type="submit"><?= $isEdit ? '更新する' : '作成する' ?></button>
      </form>
    </main>
  </div>
</div>

<!-- Vue & Component -->
<script type="module">
import { createApp, reactive } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
import ComponentInput from "/dashboard/ai/component-input.js";

createApp({
  components: { ComponentInput },
  setup() {
    const form = reactive(<?= json_encode($amenities_data, JSON_UNESCAPED_UNICODE); ?>);
    const template = <?= json_encode($room_template ?? [], JSON_UNESCAPED_UNICODE); ?>;
    const handleSubmit = () => document.querySelector('form').submit();
    return { form, template, handleSubmit };
  }
}).mount("#app");
</script>
</body>
</html>
