<?php
require_once dirname(__DIR__) . '/core/guest_head.php';
?>


<!DOCTYPE html>
<html lang="ja">
  <head>
    <?php include('../compornents/head.php'); ?>
    <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>
    <link rel="stylesheet" href="/assets/css/guest.css?id=<?= rand() ?>">
    <meta name="robots" content="noindex, nofollow">



    <script type="text/javascript">
      $(window).on("load", function() {
      });
    </script>

    <title>満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿 - ゲストページ　- AI チャット</title>

    <?php require_once dirname(__DIR__) . '/core/guest_header.php'; ?>
  </head>
  <body>


  <div id="guest_chat_wrap">
    <?php include_once( dirname(__DIR__) . "/chat/index.php") ?>
  </div>

    <?php include('../compornents/guest_footer.php'); ?>
    <?php include('../compornents/menu.php'); ?>
  </body>
</html>

<script>
$(document).ready(function() {
  setChatWrapHeight();

  // ウィンドウサイズが変わるたびに高さを更新
  $(window).resize(function() {
    setChatWrapHeight();
  });

  function setChatWrapHeight() {
    var windowHeight = $(window).height();
    if (navigator.userAgent.match(/iPhone|Android.+Mobile/)) {
      $('#guest_chat_wrap').css('height', windowHeight - 15 + 'px');
    }
  }
});
</script>
