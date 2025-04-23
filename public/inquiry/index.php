<?php include('../compornents/php_header.php'); ?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include('../compornents/head.php'); ?>
  <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>

  <script type="text/javascript">
    $(window).on("load", function() {
    });
  </script>

  <title><?= l('満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿 - 予約フォーム', 'Manchikan - A Hidden Gem in Shin-Koiwa - Inquiry Form') ?></title>
</head>
<body>

<?php include('../compornents/header.php'); ?>

<main>
  <h1><?= l('お問い合わせフォーム', 'Inquiry Form') ?></h1>

  <!-- Embed the booking/inquiry form via iframe with language switching based on session -->
  <iframe
    src="https://beds24.com/booking2.php?page=enquire&amp;hideback=yes&amp;propid=223365&amp;hideheader=yes&amp;hidefooter=yes&lang=<?= $_SESSION["lang"] == "EN" ? "en" : "ja" ?>"
    width="1200"
    height="800"
    style="max-width:100%;border:none;overflow:auto;">
    <p><a href="https://beds24.com/booking2.php?page=enquire&amp;hideback=yes&amp;propid=223365&amp;hideheader=yes&amp;hidefooter=yes" title="<?= l('予約する', 'Book Now') ?>"><?= l('予約する', 'Book Now') ?></a></p>
  </iframe>
</main>

<?php include('../compornents/menu.php'); ?>
<?php include('../compornents/footer.php'); ?>

</body>
</html>
