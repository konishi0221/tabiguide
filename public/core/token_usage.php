<?php
declare(strict_types=1);

/**
 * ------------------------------
 *  PRICE TABLE (JPY)
 *  更新はここだけ編集すれば全 API に反映される
 * ------------------------------
 */
const OPENAI_RATES = [
    'gpt-4o'        => ['in' => 0.000775, 'out' => 0.0031],
    'gpt-4o-mini'   => ['in' => 0.000093, 'out' => 0.000372],
    'gpt-3.5-turbo' => ['in' => 0.0000775, 'out' => 0.0002325],
    'whisper'       => ['in' => 0.00062,  'out' => 0.00062]   // 1 token ≒ 0.02 sec
];

const GOOGLE_TTS_RATES = [
    'wavenet'  => 0.00248,   // ¥/char  ($16 / 1M)
    'standard' => 0.00062,
    'studio'   => 0.0248
];

const GOOGLE_STT_RATE_PER_SEC = 0.0004;   // $0.006 / 15sec
/**
 * Google Maps JavaScript API ― Dynamic Maps
 *   $7 / 1,000 map loads  →  ¥0.001085 / load  (¥155/USD)
 */
const GOOGLE_MAPS_RATE_PER_LOAD = 0.001085;

/**
 * OpenAI Embedding models ― 円 / token
 *   (small $0.02 /1M → ¥0.0031/1K → ¥0.0000031 /token)
 *   (large $0.13 /1M → ¥0.0202/1K → ¥0.0000202 /token)
 */
const EMBEDDING_RATES = [
    'text-embedding-3-small' => 0.0000031,
    'text-embedding-3-large' => 0.0000202,
];

function rateEmbedding(string $model): float
{
    return EMBEDDING_RATES[$model] ?? 0.0;
}

function rateOpenAI(string $model, string $dir = 'in'): float
{
    return OPENAI_RATES[$model][$dir] ?? 0.0;
}

function rateGoogleTts(string $voice = 'wavenet'): float
{
    return GOOGLE_TTS_RATES[$voice] ?? GOOGLE_TTS_RATES['wavenet'];
}

function rateGoogleStt(): float
{
    return GOOGLE_STT_RATE_PER_SEC;
}

function rateGoogleMaps(): float
{
    return GOOGLE_MAPS_RATE_PER_LOAD;
}

/**
 * DB helper ― returns cached PDO
 */
function _tu_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // core/db.php returns PDO
        $pdo = require __DIR__ . '/db.php';
    }
    return $pdo;
}

/**
 * addCost($pageUid, $gptYen, $googleYen)
 *   円換算した金額を token_usage に加算
 */
function addCost(string $uid, float $gpt = 0, float $google = 0): void
{
    if ($gpt == 0 && $google == 0) return;

    $pdo = _tu_pdo();
    $pdo->prepare(
        'INSERT INTO token_usage (page_uid, gpt_price, google_price, updated_at)
         VALUES (:uid, :gpt, :goo, NOW())
         ON DUPLICATE KEY UPDATE
           gpt_price    = gpt_price    + :gpt,
           google_price = google_price + :goo,
           updated_at   = NOW()'
    )->execute([
        ':uid' => $uid,
        ':gpt' => round($gpt, 6),
        ':goo' => round($google, 6)
    ]);
}

/**
 * chargeGPT($uid, $model, $inTokens, $outTokens)
 *   入力 / 出力トークン別に円レートを掛けて金額を計算して記録。
 *   OpenAI 公開レートを 1$ = ¥155 換算 (2025‑05)
 */
function chargeGPT(string $uid, string $model, int $inTokens = 0, int $outTokens = 0): void
{
    if ($inTokens === 0 && $outTokens === 0) return;

    // 円 / token [inRate, outRate]
    $inRate  = rateOpenAI($model, 'in');
    $outRate = rateOpenAI($model, 'out');

    [$inRate, $outRate] = [$inRate, $outRate];
    $yen = ($inTokens * $inRate) + ($outTokens * $outRate);

    if ($yen > 0) {
        $yen = round($yen, 6);
        $tokTotal = $inTokens + $outTokens;
        // ログ: [GPT cost] uid=xxx model=yyy tok=zzz yen=#
        error_log(sprintf('[GPT cost] uid=%s model=%s tok=%d yen=%.3f',
            $uid, $model, $tokTotal, $yen));

        addCost($uid, $yen, 0);
    }
}

/**
 * chargeEmbedding($uid, $model, $tokens)
 *   Embeddingsモデル別レート (円 / token) で金額を計算
 */
function chargeEmbedding(string $uid, string $model, int $tokens): void
{
    $rate = rateEmbedding($model);

    if ($rate > 0 && $tokens > 0) {
        $yen = round($tokens * $rate, 6);
        error_log(sprintf('[Embedding cost] uid=%s model=%s tok=%d yen=%.3f',
            $uid, $model, $tokens, $yen));
        addCost($uid, $yen, 0);
    }
}

/**
 * chargeGoogleTTS(string $uid, string $text, string $voice = 'wavenet')
 *   Google TTS 文字数単価を加算
 */
function chargeGoogleTTS(string $uid, string $text, string $voice = 'wavenet'): void
{
    $yen = round(mb_strlen($text) * rateGoogleTts($voice), 6);
    error_log(sprintf('[Google TTS cost] uid=%s voice=%s char=%d yen=%.3f',
        $uid, $voice, mb_strlen($text), $yen));
    addCost($uid, 0, $yen);
}

/**
 * chargeGoogleSTT(string $uid, int $sec)
 *   Google STT 秒単価を加算
 */
function chargeGoogleSTT(string $uid, int $sec): void
{
    $yen = round($sec * rateGoogleStt(), 6);
    error_log(sprintf('[Google STT cost] uid=%s sec=%d yen=%.3f',
        $uid, $sec, $yen));
    addCost($uid, 0, $yen);
}

/**
 * chargeGoogleMaps(string $uid, int $loads = 1)
 *   Google Maps Dynamic load 単価を加算
 */
function chargeGoogleMaps(string $uid, int $loads = 1): void
{
    if ($loads <= 0) return;

    $yen = round($loads * rateGoogleMaps(), 6);
    error_log(sprintf('[Google Maps cost] uid=%s load=%d yen=%.3f',
        $uid, $loads, $yen));
    addCost($uid, 0, $yen);
}

/**
 * overLimit($uid)
 *   プラン price_limit を超えたら true
 */
function overLimit(string $uid): bool
{
    $pdo = _tu_pdo();
    $st = $pdo->prepare("
      SELECT t.gpt_price + t.google_price AS total,
             p.price_limit
        FROM token_usage  t
        JOIN billing      b ON b.page_uid = t.page_uid
        JOIN plan_limits  p ON p.plan_id  = b.plan_id
       WHERE t.page_uid = :uid
       LIMIT 1
    ");
    $st->execute([':uid'=>$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row && $row['price_limit'] > 0 && $row['total'] > $row['price_limit'];
}