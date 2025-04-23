<?php
session_start();
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = 'JP';
}

function l($ja, $en) {
  $language = ($_SESSION['lang'] == 'JP') ? $ja : (isset($_SESSION['lang']) ? $en : $en);
  print $language;
}

function dd($text) {
  var_dump($text);
  exit;
}

function random() {
  $str = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPUQRSTUVWXYZ';
  return substr(str_shuffle($str), 0, 10);
}
?>
