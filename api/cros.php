<?php  
$allow = [
    'http://localhost:5173',
    'https://tabiguide-721ec.web.app',   // 本番フロント
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allow, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');              // キャッシュ分離
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
