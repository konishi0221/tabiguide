<?php
session_start();

$old = $_SESSION['form_data'] ?? [];
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_data'], $_SESSION['form_error']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>アカウント作成 - タビガイド</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="logo-container">
        <a href="/">
          <img src="/assets/images/cms_logo.png" alt="タビガイド" class="logo">
        </a>
      </div>
      <h1 class="login-title">アカウント作成</h1>
      <form action="/register/auth.php" method="POST">
        <div class="form-group">
          <label for="email">メールアドレス</label>
          <input type="email" id="email" name="email" placeholder="example@mail.com" required>
        </div>
        <div class="form-group">
          <label for="password">パスワード</label>
          <input type="password" id="password" name="password" placeholder="8文字以上で入力" required>
        </div>
        <div class="form-group">
          <label for="password_confirm">パスワード（確認）</label>
          <input type="password" id="password_confirm" name="password_confirm" placeholder="もう一度入力" required>
        </div>
        <button type="submit" class="login-button">アカウント作成</button>
        <div class="login-links">
          <a href="/login/">既にアカウントをお持ちの方</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html> 