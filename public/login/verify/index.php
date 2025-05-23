<?php
session_start();
require_once dirname(__DIR__) . '/../core/db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    echo '❌ トークンがありません。';
    exit;
}

try {
    // トークンに一致するユーザーを検索
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email_verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo '❌ 無効なトークンです。';
        exit;
    }

    if ((int)$user['is_verified'] === 1) {
        echo '❌ 無効なトークンです。';
        exit;
    }

    // 認証処理
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, email_verification_token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // 成功したらログインページへリダイレクト
    header('Location: /login/?email_verification=1');
    exit;

} catch (PDOException $e) {
    echo '❌ エラーが発生しました: ' . htmlspecialchars($e->getMessage());
    exit;
}
