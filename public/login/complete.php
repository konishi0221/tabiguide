<?php
session_start();

require_once dirname(__DIR__) . '/core/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/core/functions.php'; // ← あれば

use Valitron\Validator;

$data = $_POST;

$v = new Validator($data);
Validator::lang('ja');

$v->rule('required', ['email', 'password']);
$v->rule('email', 'email');

if (!$v->validate()) {
    $_SESSION['form_data'] = $data;
    $errors = $v->errors();
    $_SESSION['toast_error'] = 'メールアドレスまたはパスワードが正しくありません';
    header('Location: /login/');
    exit;
}

// DB からユーザーを検索
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$data['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user || !password_verify($data['password'], $user['password'])) {
    $_SESSION['form_data'] = $data;
    $_SESSION['toast_error'] = 'メールアドレスまたはパスワードが正しくありません';
    header('Location: /login/');
    exit;
}

// 未確認アカウントはログイン不可
if ((int)$user['is_verified'] === 0) {
    $_SESSION['form_data']   = $data;
    $_SESSION['toast_error'] = 'メールアドレス確認後にログインしてください';
    header('Location: /login/');
    exit;
}

// セッション保存
$_SESSION['user'] = [
    'id' => $user['id'],
    'uid' => $user['uid'],
    'email' => $user['email'],
    'name' => $user['name'],
    'user_type' => $user['user_type'],
    'is_verified' => $user['is_verified'],
];

// IDを切り替えてセッション固定化
session_regenerate_id(true);

header('Location: /dashboard/');
exit;
