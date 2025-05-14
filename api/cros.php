<?php
/* api/chat/cros.php ─ CORS 設定 */
declare(strict_types=1);
/* buffer output to avoid "headers already sent" (e.g., BOM issues) */
ob_start();

$allowed = [
    'http://localhost:5173',
    'https://app.tabiguide.net',
    'https://tabiguide-721ec.web.app',
    'https://app.tabiguide.net',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
