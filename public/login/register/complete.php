<?php
session_start();

require_once dirname(__DIR__) . '../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../core/mail_helper.php';
require_once dirname(__DIR__) . '/../core/db.php';

use Valitron\Validator;

$data = $_POST;

// バリデーション
$v = new Validator($data);
$v->rule('required', ['name', 'email', 'email_confirm', 'password', 'user_type']);
$v->rule('email', ['email', 'email_confirm']);
$v->rule('equals', 'email', 'email_confirm')->message('メールアドレスが一致しません');
$v->rule('lengthMin', 'password', 8)->message('パスワードは8文字以上で入力してください。');
$v->rule('regex', 'password', '/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/')->message('パスワードは英字と数字を含めてください');

if (!$v->validate()) {
  $errors = $v->errors();
  $_SESSION['form_errors'] = $v->errors(); // 🔥ここ！
  $_SESSION['form_data'] = $data;
  $_SESSION['form_error'] = $firstError;


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
    $stmt = $pdo->prepare("INSERT INTO users (uid, name, email, password, user_type, is_verified, email_verification_token) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt->execute([$uid, $name, $email, $passwordHash, $user_type, $token]);

    sendVerificationMail($email, $token);

    header('Location: /login/?success=1');
    exit;
} catch (PDOException $e) {
    header('Location: /login/register.php?error=' . urlencode('登録に失敗しました: ' . $e->getMessage()));
    exit;
}
