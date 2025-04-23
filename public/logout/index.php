<?php
session_start();
session_unset();  // セッション変数を全て解除
session_destroy(); // セッション自体を破棄

// ログインページにリダイレクト
header("Location: /login/");
exit;
