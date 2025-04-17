<?php
session_start();

// var_dump($_SESSION['user']['uid']);

$old = $_SESSION['form_data'] ?? [];
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_data'], $_SESSION['form_error']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン</title>
</head>
<body>
  <h1>ログイン</h1>

  <?php if (!empty($error)): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form action="complete.php" method="post">
    <label>メールアドレス：<input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required></label><br><br>
    <label>パスワード：<input type="password" name="password" required></label><br><br>
    <button type="submit">ログイン</button>
  </form>

  <a href="/login/register/">新規登録はこちら</a>
</body>
</html>
