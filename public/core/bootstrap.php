<?php
declare(strict_types=1);
/* buffer output to avoid "headers already sent" (e.g., BOM issues) */
ob_start();
// Autoload and environment setup
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
putenv('GOOGLE_AUTH_DISABLE_CREDENTIALS_FILE_SEARCH=true');
// Use Redis session handler only in production
$appEnv = getenv('APP_ENV') ?: 'local';
if ($appEnv === 'production') {
    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', 'tcp://10.76.209.108:6379?timeout=172800');
    ini_set('session.gc_maxlifetime', '172800');
}

// セッション開始
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// デバッグ用ログ
// error_log('[BOOTSTRAP] session started with ID: ' . session_id());
// error_log('[BOOTSTRAP] SESSION DATA: ' . json_encode($_SESSION, JSON_UNESCAPED_UNICODE));
