<footer>
  <div id="footer_inner">
    <h2><?= l('満竹華庵', 'Manchikan') ?></h2>
    <div>
      <h3><?= l('施設について', 'Facility Information') ?></h3>
      <p><?= l('〒132-0031 東京都江戸川区松島４丁目２１−１４', '132-0031, Matsushima 4-21-14, Edogawa-ku, Tokyo, Japan') ?></p>
      <p>03-5734-1349</p>
      <br>
      <?= l('チェックイン時刻', 'Check-in Time') ?>: 15:00～<br>
      <?= l('チェックアウト時刻', 'Check-out Time') ?>: ～11:00<br><br>

      <?= l('※お問い合わせについてお電話が大変混み合いますのでメールフォームでのお問い合わせをお願いしております。', '※We kindly ask that you contact us via the email form, as our phone line is often busy.') ?><br><br>
      <a href="/inquiry/"><?= l('お問い合わせ', 'Contact Us') ?></a>
    </div>

    <div>
      <h3><?= l('注意事項', 'Important Notes') ?></h3>
      <?= l('※温浴のみの利用は受け付けておりません。', '※We do not accept bath-only use.') ?><br><br>

      <?= l('キャンセルポリシー', 'Cancellation Policy') ?><br>
      <?= l('不泊(NO-SHOW) 100％', 'No-show: 100%') ?><br>
      <?= l('当日 100％', 'Same day: 100%') ?><br>
      <?= l('前日 50％', '1 day prior: 50%') ?><br>
      <?= l('2日前 30％', '2 days prior: 30%') ?><br>
      <?= l('3日前 30％', '3 days prior: 30%') ?><br>
    </div>
  </div>
</footer>
<?php include_once( dirname(__DIR__) . "/chat/chat_wrap.php") ?>
<div id="copyright">
  <p>copyright @ Manchikan Ltd. all rights reserved.</p>
</div>
