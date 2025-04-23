<?php
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
