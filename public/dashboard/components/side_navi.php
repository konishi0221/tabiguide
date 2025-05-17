<?php
require_once __DIR__ . '/../../core/db.php';

$page_uid = $_GET['page_uid'] ?? '';
$page_uid_safe = urlencode($page_uid);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,100,1,0" />

<style>
  .open_in_new {
    display: inline-block;
    line-height: 14px;
    vertical-align: middle;
  }

  #side_navi {
    width: 240px;
    background: #fff;
    padding:  0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    box-sizing: border-box;
    padding-bottom:40px;
  }

  #side_navi h3 {
    padding: 10px 20px;
    margin: 0;
    font-size: 14px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  #side_navi .side_icon {
    font-size: 20px;
    color: #666;
  }

  #side_navi ul {
    list-style: none;
    margin: 0 0 20px 0;
    padding: 0;
  }

  #side_navi li {
    margin: 0;
    padding: 0;
  }
  #side_navi  a {
    display: block;
  }
  
  #side_navi li a {
    display: flex;
    align-items: center;
    padding: 8px 20px 8px 48px;

    font-size: 13px;
    transition: background-color 0.2s;
  }

  #side_navi  a:hover {
    background-color: #f5f5f5;
  }

  #side_navi li a .material-symbols-outlined {
    margin-left: 4px;
    font-size: 14px;
  }

  @media (max-width: 768px) {
    #side_navi {
      position: fixed;
      left: -240px;
      top: 0;
      bottom: 0;
      transition: left 0.3s ease;
      z-index: 1000;
      over-flow: scroll;
      max-height: 100vh;
    }

    #side_navi.open {
      left: 0;
    }

    body.menu-open::after {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
  }
</style>

<div id="side_navi">

<?php
  $host = $_SERVER['HTTP_HOST'] ?? '';
  // ローカル判定（localhost か 127.0.0.1 系）
  $isLocal = preg_match('/^(localhost|127\.0\.0\.1)/', $host);

  $guest_base_url = $isLocal
      ? 'http://localhost:5173/'          // 開発環境
      : 'https://app.tabiguide.net/'; // 本番環境

  $guest_url = $guest_base_url . $page_uid_safe;
?>

<a href="/dashboard/">
  <h3>
      <span class="material-symbols-outlined side_icon">list</span>
      <span class="h3_title">管理中の施設一覧へ</span>
  </h3>
</a>


<a href="/dashboard/facility/?page_uid=<?= $page_uid_safe ?>">
  <h3>
      <span class="material-symbols-outlined side_icon">dashboard</span>
      <span class="h3_title">ダッシュボード</span>
  </h3>
</a>

<h3><span class="material-symbols-outlined side_icon">home</span><span class="h3_title">施設情報</span></h3>
  <ul>
    <li><a href="../ai/base.php?page_uid=<?= $page_uid_safe ?>">施設基本情報</a></li>
    <li><a href="../facility/map.php?page_uid=<?= $page_uid_safe ?>">施設マップ登録</a></li>
    <li><a href="../qr/?page_uid=<?= $page_uid_safe ?>">QRコード</a></li>
    <li><a href="../rooms/index.php?page_uid=<?= $page_uid_safe ?>">部屋を作成</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">smart_toy</span><span class="h3_title">AIチャット</span></h3>
  <ul>
    <li><a href="../design/chat.php?page_uid=<?= $page_uid_safe ?>">チャット設定</a></li>
    <li><a href="../chat_log/index.php?page_uid=<?= $page_uid_safe ?>">会話ログ一覧</a></li>
    <li><a href="../ai/faq.php?page_uid=<?= $page_uid_safe ?>">よくある質問</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">map</span><span class="h3_title">周辺マップ作成</span></h3>
  <ul>
    <li><a href="../stores/list.php?page_uid=<?= $page_uid_safe ?>">近所の店舗一覧</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">palette</span><span class="h3_title">ユーザー画面</span></h3>
  <ul>
    <li><a href="../design/design.php?page_uid=<?= $page_uid_safe ?>">デザイン設定</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">settings</span><span class="h3_title">施設の設定</span></h3>
  <ul>
    <li><a href="../settings/index.php?page_uid=<?= $page_uid_safe ?>">設定画面ヘ</a></li>
    <li><a href="../billing/index.php?page_uid=<?= $page_uid_safe ?>">決済設定</a></li>
  </ul>
</div>

<script>
$(function () {
  const $menu = $('#side_navi');
  const $btn  = $('#sideToggle');   // ← ここだけ変更

  $btn.on('click', function (e) {
    e.stopPropagation();
    $menu.toggleClass('open');
    $('body').toggleClass('menu-open');
  });

  $(document).on('click', function (e) {
    if ($menu.hasClass('open') &&
        !$(e.target).closest('#side_navi, #sideToggle').length) {  // ← ここも
      $menu.removeClass('open');
      $('body').removeClass('menu-open');
    }
  });
});
</script>
