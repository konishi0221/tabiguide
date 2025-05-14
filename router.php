<?php
declare(strict_types=1);
/* buffer output to avoid "headers already sent" (e.g., BOM issues) */
ob_start();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
/* ---------- canonical redirect ---------- */
$canonicalHost = 'tabiguide.net';
$host          = $_SERVER['HTTP_HOST'] ?? '';

$shouldRedirect =                       // redirect sources
    ($host === 'app.tabiguide.net' ||    // → SPA ドメイン
     str_ends_with($host, '.run.app'))   // → Cloud Run デフォルト
    && !str_starts_with($uri, '/api/')   // API はそのまま
    && $host !== 'localhost'
    && $host !== '127.0.0.1';

if ($shouldRedirect) {
    $target = 'https://' . $canonicalHost . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $target, true, 308);       // preserve method & body
    exit;
}
/* ---------- end canonical redirect ---------- */
$public  = __DIR__ . '/public';             // doc-root (= -t public)
set_include_path(__DIR__.'/public/core'.PATH_SEPARATOR.get_include_path());

/* 1. 静的ファイル（PHP 以外）は内蔵サーバーに任せる */
$path = realpath($public.$uri);
if ($uri!=='/' && $path && is_file($path)) {
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION))!=='php') {
        return false;                       // css / js / img
    }
    require $path;                          // php → include_path 有効で実行
    exit;
}

/* 2. ディレクトリ直下の index.(php|html) */
$dir = realpath($public.rtrim($uri,'/'));
foreach(['/index.php','/index.html'] as $idx){
    if($dir && is_file($dir.$idx)){ require $dir.$idx; exit; }
}

/* 3. API ルート（doc-root 外） */
if (str_starts_with($uri, '/api/')) {
    $api = __DIR__.$uri;                    // /api/xxx.php
    is_file($api) ? require $api : http_response_code(404);
    exit;
}
if (str_starts_with($uri, '/upload/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET,HEAD,OPTIONS');
}

/* 4. それ以外は共通入口 */
require $public.'/index.php';
