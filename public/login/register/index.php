<?php
session_start();

// セッションからエラーメッセージと入力データを取得
$errors = $_SESSION['form_errors'] ?? '';
$old = $_SESSION['form_data'] ?? [];

// 一度取得したらセッションから削除（再読み込みで表示されないように）
unset($_SESSION['form_error'], $_SESSION['form_data']);
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

      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $fieldErrors): ?>
          <?php foreach ($fieldErrors as $message): ?>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php endif; ?>

      <form action="complete.php" method="post">
        <div class="form-group">
          <label for="name">名前</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="email">メールアドレス</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="email_confirm">確認用メールアドレス</label>
          <input type="email" id="email_confirm" name="email_confirm" value="<?= htmlspecialchars($old['email_confirm'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="password">パスワード</label>
          <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
          <label for="user_type">ユーザータイプ</label>
          <select id="user_type" name="user_type" required>
            <option value="owner" <?= (isset($old['user_type']) && $old['user_type'] === 'owner') ? 'selected' : '' ?>>オーナー</option>
            <option value="company_admin" <?= (isset($old['user_type']) && $old['user_type'] === 'company_admin') ? 'selected' : '' ?>>管理会社</option>
            <option value="super_admin" <?= (isset($old['user_type']) && $old['user_type'] === 'super_admin') ? 'selected' : '' ?>>管理者</option>
          </select>
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
