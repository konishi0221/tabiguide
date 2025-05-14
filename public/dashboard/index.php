<?php
session_start();
// require_once '../core/db.php';
require_once dirname(__DIR__) . '/core/dashboard_head.php';

$user_id = $_SESSION['user']['uid'] ?? null;
$user_name = $_SESSION['user']['name'] ?? 'ゲスト';

$allFacilities = [];

// オーナー施設取得（designテーブルとJOIN）
$stmt = $pdo->prepare("
    SELECT f.*, d.logo_base64, d.primary_color
    FROM facility_ai_data f
    LEFT JOIN design d ON f.page_uid = d.page_uid
    WHERE f.user_uid = ?
");
$stmt->execute([$user_id]);
$ownerFacilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 管理者としての施設も取得
$stmt = $pdo->query("
    SELECT f.*, d.logo_base64, d.primary_color
    FROM facility_ai_data f
    LEFT JOIN design d ON f.page_uid = d.page_uid
    WHERE f.managers_json IS NOT NULL
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $managers = json_decode($row['managers_json'], true);
    foreach ($managers as $m) {
        if (($m['uid'] ?? '') === $user_id) {
            $row['_role'] = $m['role'] ?? 'staff';
            $allFacilities[] = $row;
            break;
        }
    }
}

// オーナー施設に _role を付与
foreach ($ownerFacilities as $f) {
    $f['_role'] = 'owner';
    $allFacilities[] = $f;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ダッシュボード</title>
  <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <style>
  </style>
</head>
<body>
<?php include(dirname(__DIR__) . '/dashboard/components/dashboard_header.php'); ?>

<div id="app" class="container">
  <!-- <a href="/dashboard/ai/create.php" class="new-button">＋ 新規作成</a> -->
  <button class="new-button" @click="showModal = true">＋ 新規作成</button>

  <h1>管理中の施設</h1>
  <div v-if="facilities.length === 0">
    <p>現在、管理中の施設はありません。</p>
  </div>


  <div class="card-grid" v-else>
      
    <div v-for="f in facilities" :key="f.page_uid" >
      <a :href="'/dashboard/facility/?page_uid=' + encodeURIComponent(f.page_uid)">
        <div
          class="card"
          :class="f._role">
          <div class="icon-circle"
          :style="{ backgroundColor: f.primary_color || '#ccc' }"
          >
            <img :src=" '/upload/' +  f.page_uid + '/images/icon.png' " alt="icon"  onerror="this.src='/assets/images/icon_no_image.jpg'"  >
          </div>
          <h3>
            {{ f.name }}
            <span class="role-tag" v-if="f._role === 'owner'">オーナー</span>
            <span class="role-tag" v-else-if="f._role === 'manager'">共同管理者</span>
            <span class="role-tag" v-else>スタッフ</span>
          </h3>
          <p>ID: {{ f.page_uid }}</p>
        </div>
      </a>
    </div>
  </div>

  <!-- モーダル -->
  <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
    <div class="modal-content">
      <span class="material-symbols-outlined modal-close" @click="showModal = false">close</span>
      <h3>新規施設を作成</h3>

      <!-- ゼロから作成 -->
      <div class="tab-panel">
        <form action="/dashboard/ai/create.php" method="POST" >
          <label>施設の種類を選択：</label>
          <select name="facility_type" v-model="facility_type" required >
            <option value="minpaku">民泊</option>
            <option value="hotel">ホテル</option>
            <option value="ryokan">旅館</option>
            <option value="camp">キャンプ場</option>
          </select>

          <label for="facility_name">施設名を入力：</label>
          <input type="text" name="facility_name" id="facility_name" v-model="facility_name" placeholder="〇〇ハウス" required>

          <input type="hidden" name="mode" value="manual">
          <button type="submit">作成する</button>
        </form>
      </div>

    </div>
  </div>
</div>


<script>
const { createApp } = Vue;
createApp({
  data() {
    return {
      facilities: <?= json_encode(array_map(function ($f) {
        $base = json_decode($f['base_data'] ?? '{}', true);
        return [
          'name' => $base['施設名'] ?? '(名称未設定)',
          'page_uid' => $f['page_uid'],
          '_role' => $f['_role'] ?? 'staff',
          'logo_base64' => $f['logo_base64'] ?? null,
          'primary_color' => $f['primary_color'] ?? '#cccccc'
        ];
      }, $allFacilities), JSON_UNESCAPED_UNICODE); ?>,
      showModal: false,
      url: '',
      activeTab: 'url',
    };
  },
  methods: {
    createFromScratch() {
      // 仮のuid=new で空状態に飛ばす or その場で新規作成API叩いてもOK
      window.location.href = '/dashboard/ai/base.php?page_uid=new';
    }
  },
}).mount('#app');
</script>
</body>
</html>
