<?php
require_once __DIR__ . '/../../core/db.php';

$page_uid = $_GET['page_uid'] ?? '';
$page_uid_safe = urlencode($page_uid);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@40,100,1,0" />

<div id="side_navi">

  <h3><span class="material-symbols-outlined side_icon">visibility</span><span class="h3_title">ゲストビュー</span></h3>
  <ul>
    <li><a href="/guest/index.php?page_uid=<?= $page_uid_safe ?>" target="_blank">
      ゲスト用チャット <span class="material-symbols-outlined open_in_new">open_in_new</span>
    </a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">smart_toy</span><span class="h3_title">AIチャット</span></h3>
  <ul>
    <li><a href="/dashboard/ai/base.php?page_uid=<?= $page_uid_safe ?>">施設基本情報</a></li>
    <li><a href="/dashboard/ai/index.php?page_uid=<?= $page_uid_safe ?>">追記情報</a></li>
    <li><a href="/dashboard/chat_log/index.php?page_uid=<?= $page_uid_safe ?>">会話ログ一覧</a></li>
    <li><a href="/dashboard/rooms/index.php?page_uid=<?= $page_uid_safe ?>">部屋を作成</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">map</span><span class="h3_title">マップ作成</span></h3>
  <ul>
    <li><a href="/dashboard/stores/list.php?page_uid=<?= $page_uid_safe ?>">登録店舗一覧</a></li>
    <li><a href="/dashboard/stores/index.php?page_uid=<?= $page_uid_safe ?>">店舗の追加</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">palette</span><span class="h3_title">ユーザー画面</span></h3>
  <ul>
    <li><a href="/dashboard/design/index.php?page_uid=<?= $page_uid_safe ?>">ユーザー画面設定</a></li>
  </ul>

  <h3><span class="material-symbols-outlined side_icon">settings</span><span class="h3_title">施設の設定</span></h3>
  <ul>
    <li><a href="/dashboard/settings/index.php?page_uid=<?= $page_uid_safe ?>">設定画面ヘ</a></li>
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
