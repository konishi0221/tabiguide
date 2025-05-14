<?php
/**
 * Unified Voice Chat API
 * POST multipart/form-data:
 *   - uid   : facility page_uid
 *   - lang  : BCP‑47 (ja‑JP / en‑US …)   ※optional (default ja‑JP)
 *   - audio : audio/webm | audio/mp4 blob (microphone recording)
 *
 * Response (application/json):
 *   { "text": "<assistant reply>",
 *     "audio": "data:audio/mp3;base64,......" }
 */

require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/token_usage.php';
require_once dirname(__DIR__) . '/api/chat/ChatService.php';
require_once dirname(__DIR__) . '/api/google/TtsClient.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';          // OpenAI SDK

header('Content-Type: application/json; charset=utf-8');

/* ---------- 0. validate input ---------- */
$uid   = $_POST['uid']  ?? '';
$lang  = $_POST['lang'] ?? 'ja-JP';
$mode  = $_POST['mode'] ?? 'voice';   // voice | tts

/* ---------- TTS‑only mode ---------- */
if ($mode === 'tts') {
    $assistantText = trim($_POST['text'] ?? '');
    if ($assistantText === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing text for TTS']);
        exit;
    }
    try {
        $tts      = new TtsClient($_ENV['GOOGLE_TTS_KEY'] ?? '');
        $audioB64 = $tts->synthesize($assistantText, $lang);

        echo json_encode([
            'bot'   => $assistantText,                    // 統一キー
            'audio' => 'data:audio/mp3;base64,' . $audioB64
        ]);
    } catch (\Throwable $e) {
        error_log('[voice_chat TTS] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'TTS failed', 'detail' => $e->getMessage()]);
    }
    exit;
}

if (!$uid || empty($_FILES['audio']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing uid or audio']);
    exit;
}

/* ---------- 1. save & normalise temp file ---------- */
$origTmp = $_FILES['audio']['tmp_name'];
$mime    = mime_content_type($origTmp);

/* 拡張子決定 */
$ext = match ($mime) {
    'audio/webm'  => '.webm',
    'audio/ogg', 'audio/oga' => '.ogg',
    'audio/wav'   => '.wav',
    'audio/mp3',  'audio/mpeg', 'audio/mpga' => '.mp3',
    'audio/mp4',  'audio/x-m4a' => '.m4a',
    default       => '.bin',
};
$tmpFile = sys_get_temp_dir() . '/vc_' . uniqid() . $ext;
if (!move_uploaded_file($origTmp, $tmpFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'failed to move uploaded file']);
    exit;
}

/* 既に WAV でない場合は 16kHz mono WAV へ変換 */
if ($ext !== '.wav') {
    $wavFile = sys_get_temp_dir() . '/vc_' . uniqid() . '.wav';
    $cmd     = sprintf('ffmpeg -y -i %s -ar 16000 -ac 1 %s 2>&1',
                escapeshellarg($tmpFile), escapeshellarg($wavFile));
    exec($cmd, $out, $ret);
    if ($ret === 0 && file_exists($wavFile)) {
        unlink($tmpFile);
        $tmpFile = $wavFile;
        $mime    = 'audio/wav';
    } else {
        error_log('[voice_chat] ffmpeg convert failed: ' . implode("\n", $out));
    }
}

/* ---------- 2. Whisper STT ---------- */
try {
    // build client via the static helper
    $openai = \OpenAI::client($_ENV['OPENAI_API_KEY'] ?? '');
    $sttRes = $openai->audio()->transcribe([
        'model'    => 'whisper-1',
        'file'     => fopen($tmpFile, 'rb'),
        'language' => substr($lang, 0, 2),        // ja, en, ko …
        'response_format' => 'json'
    ]);
    $userText   = trim($sttRes['text'] ?? '');

    // STT debug log removed

    // Whisper コスト計上
    $whTok = (int)($sttRes['usage']['total_tokens'] ?? 0);
    if ($whTok > 0) chargeGPT($uid, 'whisper', $whTok, 0);
} catch (\Throwable $e) {
    error_log('[voice_chat] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'STT failed', 'detail' => $e->getMessage()]);
    exit;
}
if ($userText === '') {
    echo json_encode(['text' => '', 'audio' => '']);
    exit;
}

/* ---------- 3. GPT Chat (allow tool/function calling) ---------- */
try {
    // "voice" モードでも通常フローと同じくツール呼び出しを許可
    $chat = new ChatService($uid, session_id(), 'voice');

    // ChatService::ask() が自動で tool_choice を決定
    $reply         = $chat->ask($userText);   // returns ['text'=>…] or function call
    $assistantText = trim($reply['text'] ?? $reply['message'] ?? '');

    // GPT reply debug log removed

    // GPT コスト計上
    $u = $reply['usage'] ?? [];
    $inTok  = $u['prompt_tokens']     ?? 0;
    $outTok = $u['completion_tokens'] ?? 0;
    chargeGPT($uid, 'gpt-4o', $inTok, $outTok);
} catch (\Throwable $e) {
    error_log('[voice_chat] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'GPT failed', 'detail' => $e->getMessage()]);
    exit;
}

/* ---------- 4. Google TTS ---------- */
try {
    $tts      = new TtsClient($_ENV['GOOGLE_TTS_KEY'] ?? '');
    $audioB64 = $tts->synthesize($assistantText, $lang);   // returns base64 MP3

    // Google TTS コスト計上 (WaveNet)
    chargeGoogleTTS($uid, $assistantText, 'wavenet');

    // TTS length debug log removed
} catch (\Throwable $e) {
    error_log('[voice_chat] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'TTS failed', 'detail' => $e->getMessage()]);
    exit;
}

// Final response debug log removed

/* ---------- 5. respond ---------- */
echo json_encode([
    'user'  => $userText,        // ← 追加: Whisper で認識したユーザー発話
    'bot'  => $assistantText,   // アシスタント発話
    'audio' => 'data:audio/mp3;base64,' . $audioB64
]);

/* ---------- cleanup ---------- */
if (isset($tmpFile) && file_exists($tmpFile)) {
    @unlink($tmpFile);
}