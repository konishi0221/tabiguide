<?php
/**
 * /dashboard/account/delete.php
 * ログイン中ユーザー自身のアカウントを削除（論理削除）する処理。
 * 成功後はセッションを破棄し /login/?account_deleted=1 へリダイレクト
 */

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../core/dashboard_head.php';    // $pdo


$userUid = $_SESSION['user']['uid'];

// --- Check facility_ai_data dependency ---
$check = $pdo->prepare("SELECT 1 FROM facility_ai_data WHERE user_uid = ? LIMIT 1");
$check->execute([$userUid]);
if ($check->fetchColumn()) {
    // Data exists → cannot delete
    header('Location: /dashboard/account/?error=facilityExist');
    exit;
}


// --- delete the user record (hard delete) ---
$stmt = $pdo->prepare("DELETE FROM users WHERE uid = ?");
$stmt->execute([$userUid]);

// destroy session
session_destroy();

// redirect
header('Location: /login/?account_deleted=1');
exit;