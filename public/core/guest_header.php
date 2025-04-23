<?php global $design; ?>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&family=M+PLUS+Rounded+1c&family=Roboto&family=Zen+Kaku+Gothic+New&family=Shippori+Mincho+B1&family=Kosugi+Maru&family=BIZ+UDPGothic&display=swap" rel="stylesheet">

<style>
  body {
    background-color: <?= $design['background_color'] ?> !important;
    color: <?= $design['text_color'] ?> !important;
    font-family: <?= $design['font_family'] ?> !important;
  }



  #guest_hooter {
    background-color: <?= $design['primary_color']?> !important;
  }



  .hooter_link a{
    color: white !important;
  }


  .hooter_link.target a{
    background-color: white !important;
    color: <?= $design['primary_color'] ?> !important;
  }


  .input-box button span.material-symbols-outlined {
    background-color: <?= $design['secondary_color']?> !important;
  }
  .message.bot {
    background-color: <?= $design['chat_bubble_color_ai']?> !important;
  }
  .message.bot::before {
    background-color: <?= $design['chat_bubble_color_ai']?> !important;
  }

  .message.user {
    background-color: <?= $design['chat_bubble_color_user']?> !important;
  }

  .map-icon.home{
    background-color: <?= $design['primary_color']?> !important;
  }
  .map-icon.home::after {
    border-top: 8px solid <?= $design['primary_color']?> !important;
  }


  .category-bar .category_icon {
    color: <?= $design['text_color'] ?> !important;
  }

  .category-bar .category_icon.active::after {
    background-color: <?= $design['text_color'] ?> !important;
  }

  .image_icon {
    background-color: <?= $design['primary_color']?> !important;
    border: solid 2px <?= $design['primary_color']?> !important;
  }

  .image_icon::after {
    border-top: 8px solid <?= $design['primary_color']?>;
  }

  .bot_icon {
    background-color: <?= $design['primary_color']?> !important;
  }
</style>
