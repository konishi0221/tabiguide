<?php
// // ホスト名がlocalhostの場合
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    // ローカル開発環境用
    $mysqli = new mysqli('127.0.0.1', 'root', 'root', 'tabiguide', 8889);
} else {
    // リアルサーバー用（実際のサーバー情報を設定）
    $mysqli = new mysqli('mysql312.phy.lolipop.lan', 'LAA1574782', 'axis5746', 'LAA1574782-manchikan');
}

// 接続エラーチェック
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// echo "Connected successfully!";


try {
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        // ローカル環境（MAMPなど）
        $pdo = new PDO('mysql:host=127.0.0.1;port=8889;dbname=tabiguide;charset=utf8mb4', 'root', 'root');
    } else {
        // 本番環境（例: ロリポップ）
        $pdo = new PDO('mysql:host=mysql312.phy.lolipop.lan;dbname=LAA1574782-manchikan;charset=utf8mb4', 'LAA1574782', 'axis5746');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ DB接続成功！";
} catch (PDOException $e) {
    die("❌ DB接続失敗: " . $e->getMessage());
}

?>
