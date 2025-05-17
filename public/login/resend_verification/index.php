<?php
session_start();
$toastError    = $_SESSION['toast_error'] ?? '';
$toastSuccess  = $_SESSION['toast_success'] ?? '';
unset($_SESSION['toast_error'], $_SESSION['toast_success']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>確認メール再送 - Tabiguide</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <script src="/assets/js/toast.js"></script>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="logo-container">
        <a href="/">
          <img src="/assets/images/cms_logo.png" alt="Tabiguide" class="logo">
        </a>
      </div>
      <h1 class="login-title">確認メールを再送</h1>

      <form method="post" action="complete.php">
        <div class="form-group">
          <label for="email">登録メールアドレス</label>
          <input type="email" id="email" name="email" placeholder="example@example.com" value="<?= htmlspecialchars($_GET['prefill'] ?? '') ?>" required>
        </div>

        <button type="submit" class="login-button">メールを再送する</button>

        <div class="login-links">
          <a href="/login/">ログインに戻る</a>
        </div>
      </form>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const ok = <?= json_encode($toastSuccess) ?>;
    const err = <?= json_encode($toastError) ?>;
    if (ok) showToast(ok, 'success');
    if (err) showToast(err, 'error');
  });
  </script>
</body>
</html>