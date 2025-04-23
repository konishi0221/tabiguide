<?php
// // // ホスト名がlocalhostの場合
// if ($_SERVER['SERVER_NAME'] == 'localhost') {
//     // ローカル開発環境用
//     $mysqli = new mysqli('127.0.0.1', 'root', 'root', 'tabiguide', 8889);
// } else {
//     // リアルサーバー用（実際のサーバー情報を設定）
//     $mysqli = new mysqli('mysql312.phy.lolipop.lan', 'LAA1574782', 'axis5746', 'LAA1574782-manchikan');
// }
//
// // 接続エラーチェック
// if ($mysqli->connect_error) {
//     die("Connection failed: " . $mysqli->connect_error);
// }

// echo "Connected successfully!";

//
// try {
//     if ($_SERVER['SERVER_NAME'] === 'localhost') {
//         // ローカル環境（MAMPなど）
//         $pdo = new PDO('mysql:host=127.0.0.1;port=8889;dbname=tabiguide;charset=utf8mb4', 'root', 'root');
//     } else {
//         // 本番環境（例: ロリポップ）
//         $pdo = new PDO('mysql:host=mysql312.phy.lolipop.lan;dbname=LAA1574782-manchikan;charset=utf8mb4', 'LAA1574782', 'axis5746');
//     }
//
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     return $pdo;                         // ← ★ これを忘れずに
//     // echo "✅ DB接続成功！";
// } catch (PDOException $e) {
//     die("❌ DB接続失敗: " . $e->getMessage());
// }

/**
 * core/config.php
 * ──────────────────────────────
 * 1. ローカル      → 127.0.0.1:8889 / root:root
 * 2. Cloud Run本番 → 環境変数(DB_SOCKET など) を使用
 */

/* ---------- ローカル or 本番 判定 ---------- */
$isLocal = in_array(
    preg_replace('/:\d+$/', '', ($_SERVER['SERVER_NAME'] ?? '')),
    ['localhost', '127.0.0.1', '::1'],
    true
);

if ($isLocal) {
    // ── ローカル (MAMP など) ──
    $dsn     = 'mysql:host=127.0.0.1;port=8889;dbname=tabiguide;charset=utf8mb4';
    $dbUser  = 'root';
    $dbPass  = 'root';
} else {
    // ── Cloud Run ──
    $dbSocket = getenv('DB_SOCKET');                    // 例: /cloudsql/project:region:instance
    $dbName   = getenv('DB_NAME')   ?: 'tabiguide';
    $dbUser   = getenv('DB_USER')   ?: 'appuser';
    $dbPass   = getenv('DB_PASS')   ?: 'banax877';

    if ($dbSocket) {
        // Unix ソケット経由
        $dsn = "mysql:unix_socket=$dbSocket;dbname=$dbName;charset=utf8mb4";
    } else {
        // TCP 接続に切り替える場合
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    }
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
} catch (PDOException $e) {
    http_response_code(500);
    die('❌ DB接続失敗: ' . $e->getMessage());
}
