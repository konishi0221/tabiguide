<?php include('../../compornents/php_header.php');
require_once dirname(__DIR__) . '/../core/guest_head.php';

?>


<!DOCTYPE html>
<html lang="ja">
  <head>
    <?php include('../../compornents/head.php'); ?>
    <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>
    <link rel="stylesheet" href="/assets/css/guest.css?id=<?= rand() ?>">
    <meta name="robots" content="noindex, nofollow">


    <script type="text/javascript">
      $(window).on("load", function() {
      });
    </script>

    <title>満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿 - ゲストページ　- 基本情報 </title>
    <?php require_once dirname(__DIR__) . '/../core/guest_header.php'; ?>

  </head>
  <body>


  <div id="guest_info_wrap">

    <h1><?= l('基本情報', 'Basic Information') ?></h1>
    <table>
      <tr>
        <th><?= l('名称', 'Name') ?></th>
        <td><?= l('満竹華庵', 'Hotel MANCHIKAN') ?></td>
      </tr>
      <tr>
        <th><?= l('WEBサイト', 'WEB site') ?></th>
        <td><a href="https://manchikan.tokyo" target="_blank">https://manchikan.tokyo</a></td>
      </tr>
      <tr>
        <th><?= l('電話番号', 'Telephone') ?></th>
        <td>03-1234-5678</td>
      </tr>
      <tr>
        <th><?= l('所在地', 'Address') ?></th>
        <td><a href="https://maps.app.goo.gl/eyJqEcFrFKX8aJwd8" target="_blank"><?= l('〒132-0031 東京都江戸川区松島４丁目２１−１４', '4-21-14 Matsushima, Edogawa-ku, Tokyo 132-0031, Japan') ?></a></td>
      </tr>
      <tr>
        <th><?= l('階数', 'Floors') ?></th>
        <td><?= l('2階建', '2-story') ?></td>
      </tr>
      <tr>
        <th><?= l('広さ', 'Size') ?></th>
        <td>32㎡(1F) + 24㎡(2F)</td>
      </tr>
      <tr>
        <th><?= l('浴室', 'Bathroom') ?></th>
        <td><?= l('室内檜風呂', 'Indoor Hinoki Bath') ?></td>
      </tr>
      <tr>
        <th><?= l('ベッド数', 'Number of Beds') ?></th>
        <td><?= l('ダブルサイズベッドx2 布団x4セット 合計8名', '2 Double Beds x2, 4 Sets of Futons, Total 8 Persons') ?><br><?= l('※8名でご宿泊いただく場合、少々手狭に感じるかもしれません。', '※When staying with 8 people, it may feel a bit cramped.') ?></td>
      </tr>
      <tr>
        <th><?= l('備品', 'Amenities') ?></th>
        <td><?= l('ヘアドライヤー・浴衣・電気ケトル・バスタオル・フェイスタオル・シャンプー・コンディショナー・ボディソープ・歯ブラシセット・髭剃り・ヘアブラシ・ヘアゴムセット・化粧品３点セット（メイク落とし・洗顔・化粧水）', 'Hair Dryer, Yukata, Electric Kettle, Bath Towels, Face Towels, Shampoo, Conditioner, Body Soap, Toothbrush Set, Razor, Hairbrush, Hair Tie Set, 3 Cosmetic Items (Makeup Remover, Face Wash, Toner)') ?></td>
      </tr>
    </table>


    <h2><?= l('施設利用のルール', 'Facility Usage Rules') ?></h2>
    <table>
      <tr>
    <th><?= l('禁煙', 'No Smoking') ?></th>
    <td><?= l('当施設内全室禁煙。電子タバコも含む。', 'All rooms in this facility are non-smoking, including e-cigarettes.') ?></td>
</tr>
<tr>
    <th><?= l('パーティー・音楽', 'Parties & Music') ?></th>
    <td><?= l('パーティーや大音量の音楽は禁止です。', 'Parties and loud music are prohibited.') ?></td>
</tr>
<tr>
    <th><?= l('入室制限', 'Room Entry Restrictions') ?></th>
    <td><?= l('事前予約者以外の部屋への入室は禁止。発覚した場合、追加料金が請求されます。', 'Entry to rooms by non-registered guests is prohibited. Additional fees will apply if discovered.') ?></td>
</tr>
<tr>
    <th><?= l('ペット', 'Pets') ?></th>
    <td><?= l('室内にペットは入れません。', 'Pets are not allowed inside the rooms.') ?></td>
</tr>
<tr>
    <th><?= l('Wi-Fi', 'Wi-Fi') ?></th>
    <td><?= l('大容量の使用予定がある場合、ゲスト自身でWi-Fiをご用意ください。<br>ID: manchikan-2<br>PASS: 12345678', 'If you plan to use large amounts of data, please provide your own Wi-Fi.<br>ID: manchikan-2<br>PASS: 12345678') ?></td>
</tr>
<tr>
    <th><?= l('罰金', 'Fines') ?></th>
    <td><?= l('ゴミやタバコを近隣にポイ捨てした場合や、屋内で喫煙した場合、罰金5万円が徴収されます。', 'A fine of 50,000 yen will be charged for littering or smoking indoors.') ?></td>
</tr>
<tr>
    <th><?= l('チェックイン<br>チェックアウト', 'Check-In & Check-Out') ?></th>
    <td><?= l('基本的にアーリーチェックイン、レイトチェックアウトはお断りしていますが、対応可能な場合もありますのでご相談ください。', 'Early check-ins and late check-outs are generally not allowed, but may be accommodated depending on availability. Please consult.') ?></td>
</tr>

    </table>




    <style>
    #guest_info_wrap {
      font-size: 15px
    }
    table {
      width: calc(100%);
      padding-bottom: 60px;
    }
    th {
      width: calc(150px);
    }
    th,td {
      padding-bottom: 10px;
      padding-top: 10px;
      border-bottom:solid 1px #dcdcdc
    }

    #guest_info_wrap a {
      text-decoration: underline;
    }
    </style>
  </div>
    <?php include('../../compornents/guest_footer.php'); ?>
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
      $('#guest_info_wrap').css('height', windowHeight - 56 + 'px');
  }
});
</script>
