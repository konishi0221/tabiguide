<?php
function getEnvVar($key) {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) return null;

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, $key) === 0) {
            list(, $value) = explode("=", $line, 2);
            return trim($value);
        }
    }
    return null;
}


$GOOGLE_MAPS_API_KEY = getEnvVar('GOOGLE_MAPS_API_KEY');
$openai_key = getEnvVar('OPENAI_KEY');

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
