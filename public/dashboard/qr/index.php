<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

// データ取得
$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// 部屋一覧取得
$stmtRooms = $pdo->prepare("SELECT room_uid, room_name FROM rooms WHERE page_uid = ? ORDER BY room_name ASC");
$stmtRooms->execute([$page_uid]);
$rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

// $guest_url = 'https://app.tabiguide.net/' . urlencode($page_uid) . '/';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>QRコード</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="/assets/js/vue.global.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs2@0.0.2/qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <style>
    .qr-grid {
        overflow: hidden;
    }
    .qr-card {
        float: left;
        max-width: 270px;
        width: calc(100%);
        padding: 10px;
        margin-left: 20px;
        margin-top: 20px;
        border:solid 1px #dcdcdc;
        border-radius: 8px;
        position: relative;
        box-shadow: 1px 0 4px rgba(0,0,0,.08);
    }
    .qr img{
        width: calc(100% - 20px);
        margin:10px;
    }
    h3 {
        text-align: center;
    }
    .qr-row {
        white-space: nowrap;
    }
    .copy_btn {
        position: absolute;
        right: 13px;
        padding: 3px;
        padding-left: 10px;
        padding-right: 10px;
        margin-top: 3px;
    }
    .copy_btn span {
        color: white;
    }
    input.url_input {
        box-shadow: 1px 0 4px rgba(0,0,0,.08) inset;
    }
    .poster_link {
        text-decoration: underline;
    }

    @media screen {
        
    }
  </style>
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
  <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app" class="container">
  <main>
  <h1>QRコード</h1>
    <p>部屋に設置してください。</p>
  <section class="">

    <div class="qr-grid">
      <div class="qr-card">
      <h3>施設全体</h3>
        <div id="qrcode" class="qr"></div>
        <div class="qr-row">
          <input id="qr-input" class="url_input" type="text" :value="text" readonly>
          <button id="copy-btn" type="button" class="copy_btn">
            <span class="material-symbols-outlined">content_copy</span>
          </button>
        </div>

        <p class="poster_link">
          <a href="poster.php?page_uid=<?= urlencode($page_uid) ?>" target="_blank" class="btn-primary">ポスターを表示</a>
        </p>
      </div><!-- /.qr-card -->

    <?php if ($rooms): ?>
      <?php foreach ($rooms as $r): 
            $rid = substr($r['room_uid'], 0, 8);  // id用
            $roomUrl = 'https://app.tabiguide.net/' . urlencode($page_uid) . '/?room=' . urlencode($r['room_uid']);
      ?>
      <div class="qr-card">
          <h3><?= htmlspecialchars($r['room_name']) ?></h3>

          <div id="qr-<?= $rid ?>" class="qr"></div>

          <div class="qr-row">
            <input id="inp-<?= $rid ?>" class="url_input" type="text" value="<?= $roomUrl ?>" readonly>
            <button id="btn-<?= $rid ?>" class="copy_btn" type="button">
              <span class="material-symbols-outlined">content_copy</span>
            </button>
          </div>

          <p class="poster_link">
            <a href="poster.php?page_uid=<?= urlencode($page_uid) ?>&room_uid=<?= urlencode($r['room_uid']) ?>" target="_blank" class="btn-primary">ポスターを表示</a>
          </p>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    </div><!-- /.qr-grid -->
  </section>

</main>
</div>
</div>
<script>

const app = Vue.createApp({
  data() {
    return {

    };
  },
  methods: {
  }
});
app.mount('#app');
// QR 生成
const targetUrl = <?= json_encode('https://app.tabiguide.net/' . urlencode($page_uid) . '/') ?>;
document.getElementById('qr-input').value = targetUrl;
new QRCode(document.getElementById('qrcode'), {
  text: targetUrl,
  width: 256,
  height: 256,
  correctLevel: QRCode.CorrectLevel.H
});

// コピー機能
document.getElementById('copy-btn').addEventListener('click', () => {
  navigator.clipboard.writeText(targetUrl).then(() => {
    const btn = document.getElementById('copy-btn');
    const iconSpan = btn.querySelector('span');
    const originalIcon = 'content_copy';
    iconSpan.textContent = 'done';
    setTimeout(() => { iconSpan.textContent = originalIcon; }, 1500);
    showToast('コピーしました');
  });
});
</script>
<script>
// -------- 部屋別 QR --------
const rooms = <?= json_encode(array_map(function($r) use($page_uid){
  return [
    'id' => substr($r['room_uid'],0,8),
    'url' => 'https://app.tabiguide.net/' . $page_uid . '/?room=' . $r['room_uid']
  ];
}, $rooms ?? [])); ?>;

rooms.forEach(r => {
  const qrDiv = document.getElementById('qr-' + r.id);
  if (qrDiv) {
    new QRCode(qrDiv, {
      text: r.url,
      width: 256,
      height: 256,
      correctLevel: QRCode.CorrectLevel.H
    });

    // set input value
    document.getElementById('inp-' + r.id).value = r.url;

    // copy btn
    document.getElementById('btn-' + r.id).addEventListener('click', () => {
      navigator.clipboard.writeText(r.url).then(()=>{
        const icon = document.querySelector('#btn-' + r.id + ' span');
        const orig = icon.textContent;
        icon.textContent = 'done';
        setTimeout(()=>{ icon.textContent = orig; }, 1500);
        showToast('コピーしました');
      });
    });
  }
});
</script>
</body>
</html>
