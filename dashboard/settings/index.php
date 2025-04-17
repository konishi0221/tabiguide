<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

// データ取得
$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$guest_password = $row['guest_password'] ?? '';
$private_info_notes = $row['private_info_notes'] ?? '';
$managers_json = json_decode($row['managers_json'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>施設の設定</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="/assets/js/vue.global.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <style>
  .setting-section {
  background: #fff;
  padding: 1.5em;
  margin-bottom: 2em;
  border: 1px solid #ddd;
  border-radius: 8px;
}

label {
  display: block;
  margin-top: 1em;
  font-weight: bold;
}

input[type="text"],
textarea,
input[type="email"] {
  width: 100%;
  padding: 0.5em;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

textarea {
  height: 120px;
}

/* === 共同管理者関連 === */

.manager_wrap {
  margin: 20px 0;
  border: 1px solid #ccc;
  border-radius: 8px;
  overflow: hidden;
}

.manager {
  padding: 8px 12px;
  align-items: center;
  border-top: 1px solid #ccc;
  background: #fff;
  overflow: hidden;
  line-height: 40px;
}

.manager:first-child {
  border-top: none;
  border-radius: 8px 8px 0 0;
}

.manager:last-child {
  border-radius: 0 0 8px 8px;
}

.manager:not(:first-child):not(:last-child) {
  border-radius: 0;
}

.state {
  height: 40px;
  margin-bottom: 0;
  width: 200px;
  float: right;
  cursor: pointer;
}

.material-symbols-outlined.delete {
  cursor: pointer;
  margin-left: 10px;
  color: #888;
  transition: color 0.2s;
  float: right;
  height: 40px;
  line-height: 40px;
  width: 40px;
}
.material-symbols-outlined.delete:hover {
  color: #d33;
}

/* ===== 新規追加エリア ===== */

.add_manager {
  background: #f9f9f9;
  border: 1px dashed #ccc;
  border-radius: 8px;
  padding: 16px;
  position: relative;
  margin-top: 12px;
}

/* 閉じるボタン */
.add_manager .material-symbols-outlined {
  position: absolute;
  top: 10px;
  right: 12px;
  font-size: 20px;
  color: #999;
  cursor: pointer;
}
.add_manager .material-symbols-outlined:hover {
  color: #333;
}

/* メール入力＋検索ボタンの横並び */
.add_manager > div:first-of-type {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

/* 入力フォーム */
.add_manager input[type="email"] {
  flex: 1;
  min-width: 0;
  height: 40px;
  padding: 0 12px;
  font-size: 14px;
}

/* 検索ボタン */
.add_manager button {
  height: 40px;
  padding: 0 16px;
  font-size: 14px;
  line-height: 1;
  background-color: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  white-space: nowrap;
  cursor: pointer;
}
.add_manager button:hover {
  background-color: #0056b3;
}

/* 検索結果表示 */
.add_manager .result {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  border: 1px solid #ddd;
  padding: 10px 12px;
  border-radius: 4px;
  margin-top: 8px;
}

.add_manager .result span {
  font-weight: bold;
}

.add_manager .result button {
  padding: 6px 12px;
  font-size: 13px;
}

  </style>
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
  <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app" class="container">
  <main>
  <h1>施設の設定</h1>

  <!-- ✅ パスワード＆機密情報 -->


  <?php
  // dd($_SESSION);
  // echo $GLOBALS['current_access_role'];
  // dd($GLOBALS['current_access_role'] == 'owner')
  ?>

  <section class="setting-section">
    <h2>画像から施設を作成</h2>

    <form action="/dashboard/settings/auto_create.php" method="POST" enctype="multipart/form-data">
      <label for="images">スクリーンショット（最大10枚）</label>
      <input type="file" name="images[]" id="images" accept="image/*,application/pdf" multiple required><br><br>
      <input type="hidden" name="page_uid" value="<?= $_GET['page_uid'] ?>" >
      <?php
      $base_data = $row['base_data'];                  // JSON文字列
      $base_data = json_decode($base_data, true);      // ← ここで配列に戻す（true で連想配列）
      // dd($base_data['施設タイプ']);                                   // 内容確認OK
      ?>
      <input type="hidden" name="facility_type" value="<?= htmlspecialchars($base_data['施設タイプ']) ?>">


      <button type="submit">画像から施設を作成</button>
    </form>
  </section>

  <!-- ✅ 管理者設定 -->
  <section class="setting-section">
    <h2>共同管理者一覧</h2>

    <button v-if="!addManagerToggle" @click="addManagerToggle = true">新規追加
      <!-- <span class="material-symbols-outlined">add</span> -->
    </button><br>

    <div class="add_manager" v-if="addManagerToggle">
      <span class="material-symbols-outlined" @click="addManagerToggle = false">close</span>

      <div>
        <input type="email" placeholder="メールアドレスを入力..." v-model="searchEmail">
        <button @click="searchUser">検索</button>
      </div>

      <div class="result" v-if="result && result.name">
        <span>{{ result.name }}</span>
        <button @click="addUser">追加</button>
      </div>
    </div>

    <div class="manager_wrap ">
      <div class="manager" v-for="(manager, index) in managers" :key="manager.uid">
        <span>{{ manager.name }}</span>
        <span class="material-symbols-outlined delete" @click="removeManager(index)">delete</span>

        <select class="state" v-model="manager.role">
          <option value="manager">共同管理者</option>
          <option value="staff">一般スタッフ</option>
        </select>
      </div>

      <p style="text-align: center"  v-if=" Object(managers).length == 0  ">共同管理者がいません。</p>
    </div>

    <button @click="saveManagers">内容を保存</button>
  </section>

  <?php if ($GLOBALS['current_access_role'] == 'owner') { ?>
    <section class="setting-section">
      <h2>全データ削除</h2>
      <form method="POST" action="/dashboard/settings/delete_facility.php" onsubmit="return confirm('本当に削除しますか？');">
        <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">
        <button type="submit" class="danger-button">この施設を削除</button>
      </form>
    </section>
  <?php } ?>
<?php
// dd($_SESSION);
?>
</main>
</div>
</div>
<script>
const initialManagers = <?= json_encode($managers_json, JSON_UNESCAPED_UNICODE) ?>;

const app = Vue.createApp({
  data() {
    return {
      addManagerToggle: false,
      searchEmail: '',
      result: null,
      managers: initialManagers || []
    };
  },
  methods: {
    searchUser() {
      if (!this.searchEmail.match(/^[\w\.\-]+@[\w\-]+\.[\w\-\.]+$/)) {
        alert('無効なメールアドレス形式です');
        return;
      }
      axios.get('/core/search_user.php', {
        params: { email: this.searchEmail }
      }).then(res => {
        if (res.data && res.data.uid) {
          this.result = res.data;
        } else {
          this.result = null;
          alert('ユーザーが見つかりません');
        }
      });
    },
    addUser() {
      if (!this.result) return;
      if (this.managers.some(m => m.uid === this.result.uid)) {
        alert('すでに追加されています');
        return;
      }
      this.managers.push({
        name: this.result.name,
        uid: this.result.uid,
        role: 'staff'
      });
      this.result = null;
      this.searchEmail = '';
      this.addManagerToggle = false;
    },
    removeManager(index) {
      this.managers.splice(index, 1);
    },
    saveManagers() {
      axios.post('/dashboard/settings/save_managers.php', {
        page_uid: '<?= $page_uid ?>',
        managers: this.managers
      }).then(res => {
        alert('保存しました');
      });
    }
  }
});
app.mount('#app');
</script>
</body>
</html>
