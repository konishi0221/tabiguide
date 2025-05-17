<?php
/**
 * /login/resend_verification/complete.php
 * 登録済みだが未確認 (is_verified = 0) のユーザーに
 * 確認メールを再送するエンドポイント。
 *
 * フォーム: /login/resend_verification/index.php (POST: email)
 * 成功:  /login/?resend=1  → login/index.php 側で showToast
 * 失敗:  セッションに toast_error をセットして /login/resend_verification/ へ戻す
 */

declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/mail/regist_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login/resend_verification/');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['toast_error'] = '有効なメールアドレスを入力してください';
    header('Location: /login/resend_verification/');
    exit;
}

// ユーザー取得
$stmt = $pdo->prepare('SELECT id, is_verified, email_verification_token FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // 未登録: 登録ページへ誘導
    $_SESSION['toast_error'] = 'メールアドレスが見つかりませんでした。新規登録をお願いします';
    header('Location: /login/register/?prefill=' . urlencode($email));
    exit;
}

if ((int)$user['is_verified'] === 1) {
    $_SESSION['toast_error'] = 'このメールアドレスは既に確認済みです';
    header('Location: /login/resend_verification/');
    exit;
}

// トークン生成（既存があれば再利用）
$token = $user['email_verification_token'] ?: bin2hex(random_bytes(16));
if (!$user['email_verification_token']) {
    $upd = $pdo->prepare('UPDATE users SET email_verification_token = ? WHERE id = ?');
    $upd->execute([$token, $user['id']]);
}

// メール送信
if (sendVerificationMail($email, $token)) {
    // 成功 → ログインページにリダイレクト
    header('Location: /login/?resend=1');
    exit;
}

$_SESSION['toast_error'] = 'メール送信に失敗しました。時間をおいて再度お試しください';
header('Location: /login/resend_verification/');
exit;