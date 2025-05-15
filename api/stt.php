<?php
require_once __DIR__ . '/cros.php';    // ← 先頭に / を付ける


require_once dirname(__DIR__) . '/vendor/autoload.php';   // Composer autoload (OpenAI SDK)

// error_log(' とどいてる ');

$tmp  = $_FILES['audio']['tmp_name'] ?? '';
$lang = $_POST['lang'] ?? 'ja';
if (!$tmp) { http_response_code(400); exit; }

// Whisper は 16kHz mono WAV が推奨
if ($tmp && mime_content_type($tmp) !== 'audio/wav') {
    $wav = sys_get_temp_dir() . '/stt_' . uniqid() . '.wav';
    $cmd = sprintf(
        'ffmpeg -y -i %s -ar 16000 -ac 1 %s 2>&1',
        escapeshellarg($tmp),
        escapeshellarg($wav)
    );
    exec($cmd, $out, $ret);
    if ($ret === 0 && file_exists($wav)) {
        $tmp = $wav;
    }
}
register_shutdown_function(fn() => @unlink($tmp));

// Docker とローカルのどちらでも動くようにパスを解決
$cfgPath = __DIR__ . '/../core/config.php';
if (!file_exists($cfgPath)) {
    $cfgPath = __DIR__ . '/../public/core/config.php';
}
require $cfgPath;       // OPENAI_API_KEY をロード

try {
    $openai = \OpenAI::client(getenv('OPENAI_API_KEY'));
    $res    = $openai->audio()->transcribe([
        'model'    => 'whisper-1',
        'file'     => fopen($tmp, 'rb'),
        'language' => $lang,
    ]);
    error_log($res['text']);
    echo json_encode(['text' => $res['text'] ?? '']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'whisper failed', 'detail' => $e->getMessage()]);
}