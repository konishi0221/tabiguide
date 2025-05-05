<?php
/* ======================================================================
   config.php  ― 設定ファイル
   ====================================================================== */


// DB設定
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'tabiguide');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// // OpenAI API設定
// define('OPENAI_API_KEY', '');

// // CORS設定
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if (!defined('BASE_PATH')) {
//     define('BASE_PATH', dirname(__DIR__));
// }



/* config.php の先頭に追加 */
// foreach (['.env', '.env.prod'] as $f) {
//     $p = __DIR__."/{$f}";
//     if (!is_file($p)) continue;
//     foreach (file($p, FILE_IGNORE_NEW_LINES) as $l) {
//         if ($l === '' || $l[0] === '#') continue;
//         putenv($l);                // KEY=VAL をそのまま export
//     }
// }


define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tabiguide');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost:5173',
    'https://app.tabiguide.net',
    'https://tabiguide-721ec.web.app',
];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') );
$GOOGLE_MAPS_API_KEY = GOOGLE_MAPS_API_KEY;   // 変数でも使いたい場合
