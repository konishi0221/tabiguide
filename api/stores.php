<?php
require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$fid  = $_GET['fid']  ?? null;
$lang = $_GET['lang'] ?? 'ja';
if (!$fid) { http_response_code(400); exit('{"error":"fid 必須"}'); }

/* ---------- DB ---------- */
$stmt = $pdo->prepare('SELECT * FROM stores WHERE facility_uid = ?');
$stmt->execute([$fid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($lang === 'ja' || empty($rows)) {
  echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ---------- 翻訳対象を配列に ---------- */
$texts = [];
foreach ($rows as $r) {
  $texts[] = $r['name']        ?? '';
  $texts[] = $r['description'] ?? '';
}

/* ---------- Google 翻訳（POST） ---------- */
$url = 'https://translation.googleapis.com/language/translate/v2';
$base = http_build_query([
  'key'    => $GOOGLE_MAPS_API_KEY,
  'target' => $lang,
  'format' => 'text'
]);

// q= を手動で連結
$query = $base;
foreach ($texts as $q) {
  $query .= '&q=' . urlencode($q);
}

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $query,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 5,
]);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
if (!isset($data['data']['translations'])) {
  // 失敗時は原文を返す
  echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$translated = array_column($data['data']['translations'], 'translatedText');

/* ---------- マッピング ---------- */
$i = 0;
foreach ($rows as &$r) {
  $r['name']        = $translated[$i++] ?? $r['name'];
  $r['description'] = $translated[$i++] ?? $r['description'];
}
unset($r);

echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
