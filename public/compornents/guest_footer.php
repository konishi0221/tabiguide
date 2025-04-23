<?php
$page_uid = $_GET['page_uid'] ?? $_SESSION['page_uid'] ?? null;
if (!$page_uid) {
    echo "施設が選択されていません。";
    exit;
}
$page_param = '?page_uid=' . urlencode($page_uid);

$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$is_map_page = rtrim($current_path, '/') == '/guest/map';
$is_chat_page = rtrim($current_path, '/') == '/guest';
$is_info_page = rtrim($current_path, '/') == '/guest/infomation';
?>


<div id="guest_hooter">
  <div id="inner">
    <div class="hooter_link <?= $is_chat_page ? "target" : "" ?>">
      <a href="/guest/<?= $page_param ?>">
        <span class="material-symbols-outlined">chat</span>
        <span class="text"><?= l('AI チャット', 'AI chat') ?></span>
      </a>
    </div>

    <div class="hooter_link <?= $is_map_page ? "target" : "" ?>" >
      <a href="/guest/map/<?= $page_param ?>">
        <span class="material-symbols-outlined">map_search</span>
        <span class="text"><?= l('周辺マップ', 'Local map') ?></span>
      </a>
    </div>

    <div class="hooter_link <?= $is_info_page ? "target" : "" ?>" >
      <a href="/guest/infomation/<?= $page_param ?>">
        <span class="material-symbols-outlined">info</span>
        <span class="text"><?= l('基本情報', 'Infomation') ?></span>
      </a>
    </div>

    <div class="hooter_link">
      <a id="translate">
        <span class="material-symbols-outlined">translate</span>
        <span class="text"><?= l('言語変更', 'Langage') ?></span>
      </a>
    </div>
  </div>
</div>
<script>
  $('#translate').click(() => {
    $('#translate_form').submit();
  })
</script>
