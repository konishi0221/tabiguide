<div id="menu_wrap">
  <span id="close" class="material-symbols-outlined"><?= l('Close', 'Close') ?></span>

  <ul>
    <a href="/">
      <li class="menu_list">
        <?= l('トップページ', 'Home') ?>
      </li>
    </a>

    <a href="/reservation/">
      <li class="menu_list">
        <?= l('ご予約', 'Reservation') ?>
      </li>
    </a>

    <a href="https://banax.tokyo/" target="_blank">
      <li class="menu_list">
        <?= l('運営会社', 'Operating Company') ?><span class="material-symbols-outlined">open_in_new</span>
      </li>
    </a>

    <a href="/guest/">
      <li class="menu_list">
        <?= l('宿泊者用ページ', 'Guest Page') ?>
      </li>
    </a>

    <a href="/inquiry/">
      <li class="menu_list">
        <?= l('お問い合わせ', 'Contact Us') ?>
      </li>
    </a>
  </ul>
</div>
