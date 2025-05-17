<?php
session_start();

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$toastError = $_SESSION['toast_error'] ?? '';
unset($_SESSION['toast_error']);
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
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

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
      <h1 class="login-title">ログイン</h1>

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
          <a href="register/">アカウントをお持ちでない方</a><br><br>
          <a href="resend_verification/">確認メールを再送する</a>
        </div>
      </form>
    </div>
  </div>
</body>
<!-- toast for query parameters & session errors -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const q = new URLSearchParams(window.location.search);

  // session-side error message passed from PHP
  const toastError = <?= json_encode($toastError ?? '') ?>;
  if (toastError) showToast(toastError, 'error');

  // Registration completed
  if (q.get('success') === '1') {
    showToast('仮登録が完了しました。確認メールを確認して登録を完了してください。', 'success');
  }

  // Verification mail resent
  if (q.get('resend') === '1') {
    showToast('確認メールを再送しました。メールをご確認ください。', 'success');
  }

  // URL-based events
  if (q.get('account_deleted') === '1') {
    showToast('アカウントを削除しました。ご利用ありがとうございました。', 'success');
  }

  if (q.get('email_verification') === '1') {
    showToast('メールアドレスが確認されました。ログインしてください。', 'success');
  }

  if (q.get('error') === 'loginFail') {
    showToast('メールアドレスまたはパスワードが正しくありません。', 'error');
  }
});
</script>
</html>
