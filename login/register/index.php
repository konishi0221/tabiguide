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
  <title>ユーザー登録</title>
</head>
<body>
  <h1>ユーザー登録フォーム</h1>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $fieldErrors): ?>
      <?php foreach ($fieldErrors as $message): ?>
        <p style="color:red;"><?= htmlspecialchars($message) ?></p>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <form action="complete.php" method="post">
    <label>名前：<input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>"></label><br><br>
    <label>メールアドレス：<input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"></label><br><br>
    <label>確認用メールアドレス：<input type="email" name="email_confirm" value="<?= htmlspecialchars($old['email_confirm'] ?? '') ?>"></label><br><br>

    <label>パスワード：<input type="password" name="password" required></label><br><br>
    <label>ユーザータイプ：
      <select name="user_type" required>
        <option value="owner" <?= (isset($old['user_type']) && $old['user_type'] === 'owner') ? 'selected' : '' ?>>オーナー</option>
        <option value="company_admin" <?= (isset($old['user_type']) && $old['user_type'] === 'company_admin') ? 'selected' : '' ?>>管理会社</option>
        <option value="super_admin" <?= (isset($old['user_type']) && $old['user_type'] === 'super_admin') ? 'selected' : '' ?>>管理者</option>
      </select>
    </label><br><br>

    <button type="submit">登録</button>
  </form>
</body>
</html>
