<?php include('./compornents/php_header.php'); ?>
<?php
// print_r($_SESSION['conversation_history']);
// exit;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include('./compornents/head.php'); ?>
  <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>

  <script type="text/javascript">
    $(window).on("load", function() {
        $("#main").bgswitcher({
        images: [
          "https://lh3.googleusercontent.com/p/AF1QipMxOzTuUo9lV5QDSmY942_JCCDna67KlqenpHIU=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipOfI3oGQ3OI2YZnZSMVWU6eWqElHQDgQn1SJO8Q=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipMDuWhRcUDn_IxL_Xo2AOYASQ1yRDdcEoFCqPJ7=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipOamRiSJnkWz3GbTiCTyzXU_LXxhTdcVu5vA6FD=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipOgQYpOhYV44xM_SN539OIyAvevGwVJ9kcL2ZAE=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipMZykkNC11c5oViUBUdGIvhefkWVI2M_sDR4T6a=s680-w680-h510",
          "https://lh3.googleusercontent.com/p/AF1QipMue5Obb-WbUXsPzlL_69j2dRsnE9r3Tje_RVSf=s680-w680-h510",
        ],
      });
      // $(".image_detail").click((event) => {
      //   $("#hover_image_wrap").fadeToggle(300)
      //   console.log($(event).data('name'))
      // })

      $(".image_detail").on('click', function () {
        // console.log($(this).data("name"));
        name = $(this).data("name")
        url = $(this).data("url")

        $("#hover_image_name").text(name)
        $("#hover_image").attr('src', url)


        $("#hover_image_wrap").fadeToggle(300)
      })

      $("#hover_image_wrap").click(() => {
        $("#hover_image_wrap").fadeToggle(300)
      })

    });
  </script>

  <link rel="stylesheet" href="/assets/css/index.min.css?id=<?= rand() ?>">
  <link rel="stylesheet" href="/assets/css/detail.min.css?id=<?= rand() ?>">
  <title><?= l('満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿', 'Manchikan - A Hidden Gem in Shin-Koiwa') ?></title>
</head>
<body>

<?php include('./compornents/header.php'); ?>

  <div id="main"></div>

  <div id="manchikan_text_wrap">
    <div id="manchikan_text" >
      <div id="manchikan_text" class="<?= $_SESSION['lang'] == "EN" ? 'en' : '' ?>" >
        <h1><?= l('新小岩に佇む、隠れ家の宿', 'A Hidden Inn in Shin-Koiwa') ?></h1>
        <?= l('満竹華庵（まんちかん）は、東京都新小岩に佇む、歴史ある古民家を改装した一棟貸しの宿。<br>江戸の風情を感じる下町・新小岩は、東京駅からわずか15分という利便性を持ちながら、どこか懐かしい空気が流れる街。<br>喧騒から一歩離れたこの場所で、静寂と安らぎに包まれる特別な時間をお過ごしいただけます。<br><br>日本の美意識を追求し、細部にまでこだわり抜いた設えが、侘び寂びの精神と現代の感性を融合。<br>過去を懐かしむだけでなく、新たな日本の美を探求する場として生まれました。<br><br>一棟貸しのため、誰にも邪魔されることなく、思いのままに過ごせるのも魅力の一つ。<br>歴史ある空間の中で、静けさと向き合い、心を整える贅沢なひとときをお楽しみください。<br><br>新小岩という下町の温もりを感じながら、ゆるやかに流れる時間に身をゆだねる滞在を。<br>満竹華庵で、日本文化の奥深さと静寂の美を体験してみませんか？',
        'Manchikan (Manchikan) is a whole-house rental inn located in Shin-Koiwa, Tokyo. Shin-Koiwa, a downtown area with the charm of the Edo period, is just 15 minutes from Tokyo Station. You can enjoy a special time surrounded by peace and tranquility away from the hustle and bustle. <br><br>With a focus on the Japanese aesthetic, every detail has been carefully crafted to combine the spirit of wabi-sabi with modern sensibility. The inn is not just about reminiscing the past, but also about exploring new Japanese beauty.<br><br>Since the whole house is rented out, guests can enjoy their time without being disturbed. Experience the luxury of being in a historical space, facing quietness, and refreshing your mind.<br><br>Relax in the warm atmosphere of Shin-Koiwa and enjoy the tranquil time that flows slowly. Experience the depth of Japanese culture and the beauty of tranquility at Manchikan.') ?>
      </div>
    </div>
    <div id="manchikan_text_image" style="background-image: url('https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664402.jpg?k=a1253d2f09f0a8b1c88353a67a187e2db0c475d93617879a870dfd96d64eb357&o=&hp=1')"></div>
  </div>

  <div id="base_info_wrap" class="clear">
  </div>
<main>
  <h2 class="top_border"><?= l('客室紹介', 'Room Introduction') ?></h2>
  <div id="detail_wrap">
    <img src="/assets/images/layout.png" id="detail_map">
    <div id="detail">
      <table>
        <tr>
          <th><?= l('階数', 'Floors') ?></th>
          <td><?= l('2階建', '2 Floors') ?></td>
        </tr>
        <tr>
          <th><?= l('広さ', 'Area') ?></th>
          <td><?= l('32㎡(1F) + 24㎡(2F)', '32㎡(1F) + 24㎡(2F)') ?></td>
        </tr>
        <tr>
          <th><?= l('浴室', 'Bathroom') ?></th>
          <td><?= l('室内檜風呂', 'Indoor Hinoki Bath') ?></td>
        </tr>
        <tr>
          <th><?= l('ベッド数', 'Number of Beds') ?></th>
          <td><?= l('ダブルサイズベッドx2 布団x4セット 合計8名<br>※8名でご宿泊いただく場合、少々手狭に感じるかもしれません。', 'Double-sized beds x2, futon x4 sets, total of 8 people<br>※Please note that if staying with 8 people, it may feel a bit cramped.') ?></td>
        </tr>
        <tr>
          <th><?= l('備品', 'Amenities') ?></th>
          <td><?= l('ヘアドライヤー・浴衣・電気ケトル・バスタオル・フェイスタオル・シャンプー・コンディショナー・ボディソープ・歯ブラシセット・髭剃り・ヘアブラシ・ヘアゴムセット・化粧品３点セット（メイク落とし・洗顔・化粧水）', 'Hair dryer, yukata, electric kettle, bath towels, face towels, shampoo, conditioner, body soap, toothbrush set, razor, hairbrush, hair tie set, 3-piece cosmetics set (makeup remover, face wash, toner)') ?></td>
        </tr>
        <tr>
          <th><?= l('注意事項', 'Important Notes') ?></th>
          <td><?= l('全室禁煙, ペットの宿泊不可', 'All rooms are non-smoking, pets are not allowed') ?></td>
        </tr>
      </table>
    </div>
  </div>


  <div id="image_wrap">
    <?php
    $room_image = [
      ['ROOM1', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664391.jpg?k=92bd6c0bc41da0ff1769452dd3fe9fea8203cd686e81b003e920f2504715818e&o=&hp=1'],
      ['ROOM2', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664396.jpg?k=d6071ece257d3f833a054b3fc62ae62f09d8efdd1ce1705c1e632882c90afa79&o=&hp=1'],
      ['ROOM3', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664385.jpg?k=f00b5f0e391d60d3dabdef359e30d88d7884b6da106f8183536c32027ea0ab3b&o=&hp=1'],
      ['BATH', 'https://q-xx.bstatic.com/xdata/images/hotel/max500/528664378.jpg?k=a4036b7d41383d11511734c8cf40bc2881a24c75665eb38de22dbb06b5c41502&o='],
      ['LIVING ROOM', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664399.jpg?k=412953a2dfd30747de6240e93851a4f9b756f72e14c7b932080876f5ba01c331&o=&hp=1'],
      ['ENTRANCE', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/528664391.jpg?k=92bd6c0bc41da0ff1769452dd3fe9fea8203cd686e81b003e920f2504715818e&o=&hp=1'],
    ];

    foreach ($room_image as $val) { ?>
      <div class="image_detail" data-name="<?= $val[0] ?>" data-url="<?= $val[1] ?>">
        <img src="<?= $val[1] ?>" data-name="child"><p><?= $val[0] ?></p>
      </div>
    <?php } ?>
  </div>






  <div>
    <h2 class="top_border"><?= l('交通案内', 'Transportation Guide') ?></h2>
    <div id="maps">
      <a target="_blank" href="https://maps.app.goo.gl/AgzRj5Fp7tSJ2hQa7">
        <img id="map_image" src="/assets/images/map_point.png">
      </a>
      <div id="map_detail">
        <div class="way">
          <h3><?= l('住所', 'Address') ?></h3>
          <p><?= l('〒132-0031 東京都江戸川区松島４丁目２１−１４', '132-0031, Matsushima 4-21-14, Edogawa-ku, Tokyo, Japan') ?></p>
        </div>

        <div class="way">
          <h3><?= l('電車', 'By Train') ?></h3>
          <p><?= l('JR 中央・総武線 新小岩駅 徒歩10分', 'JR Chuo-Sobu Line, Shin-Koiwa Station, 10 minutes on foot') ?><br>
            <?= l('※ホテルには併設の駐車場がございません。近隣駐車場をご利用ください', '※The hotel does not have an on-site parking lot. Please use nearby parking.') ?>
          </p>
        </div>

        <div class="way">
          <h3><?= l('空港', 'By Airport') ?></h3>
          <p><?= l('羽田空港　電車：約65分', 'Haneda Airport: By train, approx. 65 minutes') ?><br>
             <?= l('成田空港　電車：約80分', 'Narita Airport: By train, approx. 80 minutes') ?><br></p>
        </div>
      </div>
    </div>


  </div>
<div class="clear"></div>
</main>


<a href="/reservation/">
  <div id="reservation_btn">
    <?= l('ご予約はこちら', 'Reserve Now') ?>
  </div>
</a>

  <?php include('./compornents/menu.php'); ?>
  <?php include('./compornents/footer.php'); ?>

  <div id="hover_image_wrap">
    <span id="image_close" class="material-symbols-outlined">Close</span>

    <div class="hover_image_detail">
      <img id="hover_image" src="https://q-xx.bstatic.com/xdata/images/hotel/max500/528664378.jpg?k=a4036b7d41383d11511734c8cf40bc2881a24c75665eb38de22dbb06b5c41502&o=">
      <p id="hover_image_name">ROOM1</p>
    </div>
  </div>


</body>
</html>
