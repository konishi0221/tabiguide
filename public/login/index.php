<?php
session_start();

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ja">
  
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン - タビガイド</title>
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
      <h1 class="login-title">ログイン</h1>
      <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form action="complete.php" method="post">
        <div class="form-group">
          <label for="email">メールアドレス</label>
          <input type="email" id="email" name="email" placeholder="example@mail.com" required>
        </div>
        <div class="form-group">
          <label for="password">パスワード</label>
          <input type="password" id="password" name="password" placeholder="8文字以上で入力" required>
        </div>
        <button type="submit" class="login-button">ログイン</button>
        <div class="login-links">
          <a href="register/">アカウントをお持ちでない方</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
