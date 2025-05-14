<?php
require_once __DIR__.'/../../core/db.php';
require_once __DIR__.'/../../core/config.php';
require_once __DIR__.'/../../core/category.php';
require_once __DIR__.'/../../core/gcs_helper.php';   // gcsUpload()
require_once __DIR__.'/../../core/functions.php';    // processImage()

require_once __DIR__.'/../../core/token_usage.php';   // chargeGPT(), chargeGoogle(), overLimit()
require_once __DIR__.'/../../lib/embedding_util.php';   // makeEmbedding()

function insertNearbyStores(string $page_uid): void
{
    global $pdo, $GOOGLE_MAPS_API_KEY, $category_list, $openai_key;

    /* 詳細ログを残す */
    $debugLog = function ($msg) use ($page_uid) {
        error_log("[get_stores][$page_uid] " . $msg);
    };

    /* 座標取得 */
    $geo = $pdo->prepare('SELECT geo_data FROM facility_ai_data WHERE page_uid=?');
    $geo->execute([$page_uid]);
    $pos = json_decode($geo->fetchColumn() ?: '{}', true);
    $lat = $pos['緯度'] ?? null;
    $lng = $pos['経度'] ?? null;
    if (!$lat || !$lng) return;

    /* 既存 place_id */
    $stmt = $pdo->prepare('SELECT google_place_id FROM stores WHERE facility_uid=?');
    $stmt->execute([$page_uid]);
    $existing = array_flip(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'google_place_id')));

    $googleType = [
        'tour'=>'tourist_attraction','conveni'=>'convenience_store','essentials'=>'supermarket',
        'laundry'=>'laundry','parking'=>'parking','restaurant'=>'restaurant','cafe'=>'cafe','other'=>'store'
    ];
    $category_limits = [
        'tour'=>6,'conveni'=>3,'essentials'=>4,'laundry'=>3,'parking'=>3,'restaurant'=>6,'cafe'=>3,'other'=>0
    ];


    /* ---------- STEP‑A : Nearby を並列取得 ---------- */
    $mh          = curl_multi_init();
    $nearHandles = [];
    $nearbyRaw   = [];

    foreach ($googleType as $key => $gType) {
        $radius = 800;
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?' . http_build_query([
            'location'=>"$lat,$lng",
            'radius'  =>$radius,
            'type'    =>$gType,
            'language'=>'ja',
            'key'     =>$GOOGLE_MAPS_API_KEY
        ]);
        // Cost accounting: 1 Google call
        chargeGoogleMaps($page_uid);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        curl_multi_add_handle($mh, $ch);
        $nearHandles[$key] = $ch;
    }
    do { curl_multi_exec($mh, $running); } while ($running);
    foreach ($nearHandles as $key => $ch) {
        $nearbyRaw[$key] = json_decode(curl_multi_getcontent($ch), true);
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);

    /* ---------- STEP‑B : カテゴリ別候補配列を作成 ---------- */
    $candidates = [];                 // [catKey => [ ... ]]
    foreach ($googleType as $key => $gType) {
        $limitFetch = ($category_limits[$key] ?? 0) * 2;
        $candidates[$key] = [];
        $res = $nearbyRaw[$key] ?? null;
        if (($res['status'] ?? '') !== 'OK') {
            $debugLog("Nearby {$key} status=".($res['status'] ?? 'null'));
            continue;
        }
        foreach ($res['results'] as $p) {
            $pid = $p['place_id'] ?? '';
            if (!$pid || isset($existing[$pid])) continue;
            $candidates[$key][] = [
                'category'  => $key,
                'place_id'  => $pid,
                'name'      => $p['name'] ?? '',
                'vicinity'  => $p['vicinity'] ?? '',
                'lat'       => $p['geometry']['location']['lat'] ?? null,
                'lng'       => $p['geometry']['location']['lng'] ?? null,
                'photo_ref' => $p['photos'][0]['photo_reference'] ?? null
            ];
            if (count($candidates[$key]) >= $limitFetch) break;
        }
    }

    /* ---------- STEP‑C : GPT をカテゴリごと並列呼び出し ---------- */
    $mh = curl_multi_init();
    $gptHandles = [];
    foreach ($candidates as $key => $cand) {
        if (!$cand) continue;
        $payload = [
            'model'       => 'gpt-4o-mini',
            'temperature' => 0.2,
            'messages'    => [
                ['role'=>'system',
                 'content'=>"あなたは旅行者向けローカルガイド。JSON配列で返す。各要素は{place_id,keep,desc}。descは200字程度、日本語、数値評価は書かない。keep==1は最大{$category_limits[$key]}件。"],
                ['role'=>'user','content'=>json_encode(['places'=>$cand], JSON_UNESCAPED_UNICODE)]
            ]
        ];
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer {$openai_key}"
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20
        ]);
        curl_multi_add_handle($mh, $ch);
        $gptHandles[$key] = $ch;
    }
    do { curl_multi_exec($mh, $running); } while ($running);

    $gptResults = [];    // [catKey => decoded array]
    foreach ($gptHandles as $key => $ch) {
        $apiRaw = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);

        $decoded = json_decode($apiRaw, true) ?: [];
        $content = $decoded['choices'][0]['message']['content'] ?? '[]';
        $clean   = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($content));
        $gptResults[$key] = json_decode($clean, true) ?: [];

        /* ---- Cost accounting: GPT total_tokens ---- */
        if (!empty($decoded['usage']['total_tokens'])) {
            chargeGPT($page_uid, 'gpt-4o-mini', (int)$decoded['usage']['total_tokens']);
        }
    }
    curl_multi_close($mh);

    /* ---------- STEP‑D : 保存ループ ---------- */
    $perCatSaved = [];
    $totalSaved  = 0;
    foreach ($gptResults as $key => $dec) {
        $savedThisCat = 0;
        foreach ($dec as $d) {
            if ($savedThisCat >= ($category_limits[$key] ?? 0)) break;
            if (($d['keep'] ?? 0) != 1) continue;

            $idx = array_search($d['place_id'] ?? '', array_column($candidates[$key], 'place_id'));
            if ($idx === false) continue;
            $src = $candidates[$key][$idx];
            if (isset($existing[$src['place_id']])) continue;

            $uid = substr(bin2hex(random_bytes(8)), 0, 16);
            try {
                $pdo->prepare('INSERT INTO stores (facility_uid,category,name,description,lat,lng,is_visible,uid,google_place_id)
                               VALUES (?,?,?,?,?,?,1,?,?)')
                    ->execute([
                        $page_uid,
                        $key,                       // 文字キーをそのまま保存
                        $src['name'],
                        $d['desc'] ?? '',
                        $src['lat'],
                        $src['lng'],
                        $uid,
                        $src['place_id']
                    ]);

                /* ----- FAQ upsert & embedding for AI search ----- */
                try {
                    // (a) build composite_key and question/answer
                    $compositeKey   = "{$page_uid}_{$uid}";
                    $questionTitle  = "{$src['name']} はどこですか？";
                    $answerFull     = $d['desc'] ?? '';

                    // (b) upsert into question table
                    $qSql = 'INSERT INTO question
                               (page_uid, composite_key, question, answer, type, mode, state)
                             VALUES
                               (?,?,?,?, "facility","guest","reply")
                             ON DUPLICATE KEY UPDATE
                               composite_key = VALUES(composite_key),
                               question      = VALUES(question),
                               answer        = VALUES(answer),
                               state         = "reply",
                               id            = LAST_INSERT_ID(id)';
                    $pdo->prepare($qSql)->execute([
                        $page_uid,
                        $compositeKey,
                        $questionTitle,
                        $answerFull
                    ]);

                    // (c) obtain id
                    $qid = $pdo->lastInsertId() ?: $pdo->query(
                        'SELECT id FROM question WHERE composite_key = '.$pdo->quote($compositeKey).' LIMIT 1'
                    )->fetchColumn();

                    // (d) build embedding and persist
                    $content = "Q: {$questionTitle}\nA: {$answerFull}";
                    $vecJson = makeEmbedding($page_uid, $content);

                    $pdo->prepare('UPDATE question SET embedding=:emb WHERE id=:id')
                        ->execute([':emb'=>$vecJson, ':id'=>$qid]);
                } catch (Throwable $e) {
                    $debugLog("FAQ/embedding ERROR: ".$e->getMessage());
                }
            } catch (Throwable $e) {
                $debugLog("PDO ERROR {$key}: ".$e->getMessage());
                continue;
            }

            /* 画像処理（失敗は無視） */
            if ($src['photo_ref']) {
                $imgBin = @file_get_contents('https://maps.googleapis.com/maps/api/place/photo?' . http_build_query([
                    'maxwidth'      => 600,
                    'photoreference'=> $src['photo_ref'],
                    'key'           => $GOOGLE_MAPS_API_KEY
                ]));
                // Cost accounting: 1 Google call for photo
                if ($imgBin) {
                    $png = processImage($imgBin, 600, 'default');
                    gcsUpload($png, "stores/{$page_uid}/{$uid}.png");
                }
            }

            $savedThisCat++;
            $totalSaved++;
            $existing[$src['place_id']] = true;
        }
        $perCatSaved[$key] = $savedThisCat;
        $debugLog("CATEGORY {$key} saved={$savedThisCat}");
    }
    /* ---------- 最終サマリー ---------- */
    foreach ($perCatSaved as $k => $n) {
        $debugLog("SUMMARY {$k}: {$n}");
    }
    $debugLog("RESULT saved_total={$totalSaved}");
    return;
}

/* cURL JSON POST */
function httpPostJson(string $url,array $json,string $apiKey): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            'Content-Type: application/json',
            "Authorization: Bearer {$apiKey}"
        ],
        CURLOPT_POSTFIELDS=>json_encode($json),
        CURLOPT_TIMEOUT=>30
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '[]', true);
}
