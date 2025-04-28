<?php
/**
 * core/db.php
 *  ───────────────────────────────
 *  .env もしくは docker run --env-file で
 *  DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS / DB_SOCKET
 *  を渡せば自動で上書きされます。
 */

/* ---------- デフォルト ---------- */
$dbName = getenv('DB_NAME') ?: 'tabiguide';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'root';

/* ---------- DSN 構築 ---------- */
if ($sock = getenv('DB_SOCKET')) {
    // Cloud SQL など Unix ソケット
    $dsn = "mysql:unix_socket=$sock;dbname=$dbName;charset=utf8mb4";
} else {
    // TCP
    $dbHost = getenv('DB_HOST') ?: 'host.docker.internal';
    $dbPort = getenv('DB_PORT') ?: '8889';
    $dsn    = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
}

/* ---------- 接続 ---------- */
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;         // ← 重要
} catch (PDOException $e) {
    $msg = '❌ DB接続失敗: '.$e->getMessage()
         .' | DSN='.$dsn.' | USER='.$dbUser;    // デバッグ用
    error_log($msg);                            // ログに残す
    echo $msg;                                  // 画面にも出す

}
