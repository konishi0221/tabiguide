<?php
require_once __DIR__ . '/db.php'; // データベース接続
header('Content-Type: application/json');

// メールアドレスの取得とバリデーション
$email = $_GET['email'] ?? '';
$email = trim($email);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => '無効なメールアドレス形式です']);
    exit;
}

// ユーザー検索
$stmt = $pdo->prepare("SELECT uid, name, email FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'uid' => $user['uid'],
        'name' => $user['name'],
        'email' => $user['email']
    ]);
} else {
    echo json_encode(['error' => 'ユーザーが見つかりません']);
}
