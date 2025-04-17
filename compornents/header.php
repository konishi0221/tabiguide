<header>
  <a href="/">
    <div id="rogo">
      <img src="/assets/images/rogo_w.png">
      <div class="title_wrap">
        <div class="jp">満竹華庵</div>
        <div class="en">Manchikan</div>
      </div>
    </div>
  </a>

  <div id="menu">
    <span class=" material-symbols-outlined">menu</span>
  </div>
  <div id="lang"><form method="post" action="/settings/langage.php"><input type="submit" name="lang" value="JP" class="<?= $_SESSION['lang'] == "JP" ? "selected" : ""  ?>"></form><span id="slash">/</span><form method="post" action="/settings/langage.php"><input type="submit" name="lang" value="EN" class="<?= $_SESSION['lang'] == "EN" ? "selected" : ""  ?>"></form></div>
</header>
<div id="header_padding">　</div>
