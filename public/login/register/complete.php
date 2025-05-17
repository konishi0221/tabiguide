<?php
session_start();

require_once dirname(__DIR__) . '../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../core/mail/regist_mail.php';
require_once dirname(__DIR__) . '/../core/db.php';

global $pdo; // make sure $pdo from db.php is in scope

use Valitron\Validator;

$data = $_POST;
// user_type が送られてこなければ "owner" を初期値にする
if (empty($data['user_type'])) {
    $data['user_type'] = 'owner';
}

// バリデーション
$v = new Validator($data);
$v->rule('required', ['name', 'email', 'email_confirm', 'password', 'user_type']);
$v->rule('email', ['email', 'email_confirm']);
$v->rule('equals', 'email', 'email_confirm')->message('メールアドレスが一致しません');
$v->rule('lengthMin', 'password', 8)->message('パスワードは8文字以上で入力してください。');
$v->rule('regex', 'password', '/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/')->message('パスワードは英字と数字を含めてください');

if (!$v->validate()) {
  $errors = $v->errors();
  $_SESSION['form_errors'] = $errors;
  $_SESSION['form_data']   = $data;
  // 最初のエラーメッセージを 1 件だけ取り出す
  $firstErrorMsg = '';
  foreach ($errors as $fld => $arr) {
      $firstErrorMsg = $arr[0] ?? '';
      if ($firstErrorMsg) break;
  }
  $_SESSION['form_error'] = $firstErrorMsg;

  header('Location: /login/register/');
  exit;
}

$name = $data['name'];
$email = $data['email'];
$passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
$user_type = $data['user_type'];
$uid = uniqid();
$token = bin2hex(random_bytes(16));

try {
    // 既に同じメールが登録されていないかチェック
    $dup = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $dup->execute([$email]);
    if ($dup->fetchColumn()) {
        $_SESSION['toast_error'] = 'このメールアドレスは既に登録されています。';
        header('Location: /login/register/');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (uid, name, email, password, user_type, is_verified, email_verification_token) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt->execute([$uid, $name, $email, $passwordHash, $user_type, $token]);

    $mailOk = sendVerificationMail($email, $token);
    if (!$mailOk) {
        error_log('[register] verification mail failed for ' . $email);
        // 失敗しても登録自体は続行し、後で管理者が再送できるようにしておく
    }

    header('Location: /login/?success=1' . ($mailOk ? '' : '&mailfail=1'));
    exit;
} catch (PDOException $e) {
    header('Location: /login/register.php?error=' . urlencode('登録に失敗しました: ' . $e->getMessage()));
    exit;
}
