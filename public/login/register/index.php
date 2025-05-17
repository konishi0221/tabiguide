<?php
session_start();

// セッションからエラーメッセージと入力データを取得
$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_data'] ?? [];

// トースト用メッセージ
$toastError = $_SESSION['toast_error'] ?? '';

// 一度取得したらセッションから削除
unset($_SESSION['toast_error'], $_SESSION['form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>アカウント作成 - タビガイド</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <script src="/assets/js/toast.js"></script>
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

      <form action="complete.php" method="post">
        <div class="form-group">
          <label for="name">名前</label>
          <input type="text" id="name" name="name" placeholder="山田太郎" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="email">メールアドレス</label>
          <input type="email" id="email" name="email" placeholder="example@example.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="email_confirm">確認用メールアドレス</label>
          <input type="email" id="email_confirm" name="email_confirm" placeholder="メールアドレスを再入力" value="<?= htmlspecialchars($old['email_confirm'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="password">パスワード</label>
          <input type="password" id="password" name="password" placeholder="半角英数8文字以上" required>
        </div>

        <button type="submit" class="login-button">アカウント作成</button>
        <div class="login-links">
          <a href="/login/">既にアカウントをお持ちの方</a>
        </div>
      </form>
    </div>
  </div>

  <!-- toast for errors -->
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const toastErr = <?= json_encode($toastError) ?>;
    if (toastErr) {
      if (typeof showToast === 'function') showToast(toastErr, 'error');
    }

    const errors = <?= json_encode(
      array_values(is_array($errors) ? array_merge(...array_values($errors)) : [])
    ) ?>;
    errors.forEach(msg => {
      if (typeof showToast === 'function') showToast(msg, 'error');
    });
  });
  </script>
</body>
</html>
