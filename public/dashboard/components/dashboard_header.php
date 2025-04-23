<?php
$page_uid = $_GET['page_uid'] ?? '';
$facility_name = '';
$user = $_SESSION['user'];

if ($page_uid) {
  $stmt = $pdo->prepare("SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
  $stmt->execute([$page_uid]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row && isset($row['base_data'])) {
    $base = json_decode($row['base_data'], true);
    $facility_name = $base['施設名'] ?? '';
  }
}
?>


<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<div class="dashboard-header" id="header">

  <button id="sideToggle" class="menu-btn">
    <span class="material-symbols-outlined">menu</span>
  </button>


  <div class="dashboard-header-left">
    <div class="dashboard-header-logo">
      <a href="/dashboard/">
        <img src="/assets/images/cms_logo.png" alt="logo">
      </a>
    </div>

    <span class="facility-name"><?= htmlspecialchars($facility_name ?? '') ?></span> <!-- ← ここ追加 -->
  </div>
  <div></div>
  <div class="dashboard-header-icons">

    <a href="/dashboard/account/">
      <img
        src="<?= isset($user['icon_base64']) && $user['icon_base64'] ? 'data:image/png;base64,' . htmlspecialchars($user['icon_base64']) : '/assets/images/default_icon.png' ?>"
        onerror="this.onerror=null;this.src='/assets/images/default_icon.png';"
        alt="User Icon"
        class="user-icon"
      />
    </a>
    <!-- <a href="">
      <span class="material-symbols-outlined">notifications</span>
    </a> -->

    <a href="/logout/">
      <span class="material-symbols-outlined">logout</span>
    </a>
  </div>
</div>

<p class="smh_facility_name"><?= htmlspecialchars($facility_name ?? '') ?></p> <!-- ← ここ追加 -->
