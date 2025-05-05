<?php
// api/facility.php
require_once __DIR__ . '/cros.php';    // ← 先頭に / を付ける

require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';


$fid  = $_GET['page_uid'] ?? ($_GET['fid'] ?? '');
$lang = $_GET['lang']     ?? 'ja';
if (!$fid){ http_response_code(400); exit('{"error":"page_uid required"}'); }

/* ── fetch ───────────────────────────────── */
$stmt = $pdo->prepare('SELECT base_data,geo_data FROM facility_ai_data WHERE page_uid=? LIMIT 1');
$stmt->execute([$fid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ http_response_code(404); exit('{"error":"not found"}'); }

$base = json_decode($row['base_data'] ?? '{}', true) ?: [];

/* ja のまま欲しい時はスキップ */
if ($lang === 'ja'){
  $row['base_data'] = json_encode($base, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $row['geo_data']  = $row['geo_data'] ?: '{}';
  echo json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ── フラット化して翻訳 ─────────────────── */
$texts = [];               // 送る文字列
$paths = []; $i = 0;       // どこに差し込むか

foreach ($base as $k => $v){
  if (is_string($v) && $v!==''){
    $texts[]     = $v;
    $paths[$i++] = $k;     // 上位キーだけなのでシンプル
  }
}

/* 空ならそのまま返す */
if (!$texts){
  $row['base_data'] = json_encode($base, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $row['geo_data']  = $row['geo_data'] ?: '{}';
  echo json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ── Google 翻訳 ─────────────────────────── */
$url  = 'https://translation.googleapis.com/language/translate/v2';
$body = http_build_query([
  'key'    => $GOOGLE_MAPS_API_KEY,
  'target' => $lang,
  'format' => 'text'
]);
foreach ($texts as $q) $body .= '&q='.urlencode($q);

$ch = curl_init($url);
curl_setopt_array($ch,[
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $body,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 5,
]);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res,true);
if(isset($data['data']['translations'])){
  $trs = array_column($data['data']['translations'],'translatedText');
  foreach ($trs as $idx=>$txt){
    $base[$paths[$idx]] = $txt;
  }
}

/* ── 出力 ───────────────────────────────── */
$row['base_data'] = json_encode($base, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$row['geo_data']  = $row['geo_data'] ?: '{}';

echo json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
