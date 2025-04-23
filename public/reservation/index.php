<?php include('../compornents/php_header.php'); ?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include('../compornents/head.php'); ?>
  <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>

  <script type="text/javascript">
    $(window).on("load", function() {
      // Your jQuery or custom scripts can go here
    });
  </script>

  <title><?= l('満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿 - 予約フォーム', 'Manchikan - A Hidden Gem in Shin-Koiwa - Reservation Form') ?></title>

</head>
<body>

<?php include('../compornents/header.php'); ?>

<main>
  <h1><?= l('予約フォーム', 'Reservation Form') ?></h1>
  <!-- bookingWidget requires jQuery and jQueryUI -->
  <!-- Include the following line ONCE ONLY on the page, preferably in the document head -->
  <script src='https://media.xmlcal.com/widget/1.00/js/bookWidget.min.js'></script>

  <!-- Place this div on your page where you want the widget to show -->
  <div id='bookWidget-118413-223365-0-1742083428'> </div>
  <br><br>
  <?= l(
    '※予約ボタンをクリックすると、予約サイトに移動します。<br><br>※ご予約の日程を選択いただき、進んでいただくと、<b>決済ページにて清掃費が加算されます</b>ので、あらかじめご留意くださいますようお願い申し上げます。',
    '※By clicking the reservation button, you will be redirected to the reservation site.<br>※After selecting your desired dates, <b>please note that a cleaning fee will be added on the payment page. </b>Please be aware of this in advance.'
    ) ?>

  <!-- The following will initialize the widget in the above div -->
  <script>
  jQuery(document).ready(function() {
    var widgetLang = '<?php echo $_SESSION["lang"] == "EN" ? "en" : "ja"; ?>';
    jQuery('#bookWidget-118413-223365-0-1742083428').bookWidget({
      propid: 223365,
      buttonBackgroundColor: '#424242',
      buttonColor: '#ffffff',
      formAction: 'https://beds24.com/booking.php',
      widgetLang: widgetLang,
      widgetType: 'BookingBox'
    });
  });
  </script>

  <style>
  #bookWidget-118413-223365-0-1742083428 .book-widget-size-all {
    box-shadow: 2px 7px 8px rgba(0,0,0,.3);
    border: solid 1px #dcdcdc;
  }

  #bookWidget-118413-223365-0-1742083428 .book-btn, .book-btn {
    border-radius: 4px;
  }
  </style>
</main>

<?php include('../compornents/menu.php'); ?>
<?php include('../compornents/footer.php'); ?>

</body>
</html>
