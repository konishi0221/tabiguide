<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/category.php';

function insertNearbyStores($page_uid) {
    global $pdo, $GOOGLE_MAPS_API_KEY, $category_list;

    $stmt = $pdo->prepare("SELECT geo_data FROM facility_ai_data WHERE page_uid = ?");
    $stmt->execute([$page_uid]);
    $geo_data = json_decode($stmt->fetchColumn() ?? '{}', true);

    $lat = $geo_data['緯度'] ?? null;
    $lng = $geo_data['経度'] ?? null;

    if (!$lat || !$lng) {
        echo "⚠ 緯度経度が取得できませんでした。";
        return;
    }

    $googleTypeMap = [
        'tour'       => 'tourist_attraction',
        'conveni'    => 'convenience_store',
        'essentials' => 'supermarket',
        'laundry'    => 'laundry',
        'parking'    => 'parking',
        'restaurant' => 'restaurant',
        'cafe'       => 'cafe',
        'other'      => 'store',
    ];

    $category_limits = [
        'tour'       => 6,
        'conveni'    => 3,
        'essentials' => 4,
        'laundry'    => 3,
        'parking'    => 3,
        'restaurant' => 6,
        'cafe'       => 3,
        'other'      => 0,
    ];

    // すでに登録されている google_place_id 一括取得
    $stmt = $pdo->prepare("SELECT google_place_id FROM stores WHERE facility_uid = ?");
    $stmt->execute([$page_uid]);
    $existing_place_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'google_place_id');
    $existing_place_ids = array_filter($existing_place_ids); // null除去
    $existing_place_ids = array_flip($existing_place_ids); // 高速検索用にflip

    $savedCount = 0;

    foreach ($category_list as $key => $cat) {
        if (!isset($googleTypeMap[$key])) continue;
        $googleType = $googleTypeMap[$key];

        $radius_steps = [100, 200, 400, 800, 1200];
        $results = [];

        foreach ($radius_steps as $radius) {
            $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&type={$googleType}&language=ja&key={$GOOGLE_MAPS_API_KEY}";
            $res = json_decode(file_get_contents($url), true);

            if (($res['status'] ?? '') === 'OK' && !empty($res['results'])) {
                $results = $res['results'];
                break;
            }
        }

        if (empty($results)) continue;

        $limit = $category_limits[$key] ?? 2;
        $inserted = 0;

        foreach ($results as $place) {
            $place_id = $place['place_id'] ?? null;
            if (!$place_id || isset($existing_place_ids[$place_id])) continue;

            $name = $place['name'] ?? '';
            $vicinity = $place['vicinity'] ?? '';
            $storeLat = $place['geometry']['location']['lat'] ?? null;
            $storeLng = $place['geometry']['location']['lng'] ?? null;
            $uid = substr(bin2hex(random_bytes(8)), 0, 16);

            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO stores (facility_uid, category, name, description, lat, lng, is_visible, uid, google_place_id)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
            ");
            $stmt->execute([$page_uid, $key, $name, $vicinity, $storeLat, $storeLng, $uid, $place_id]);
            $store_id = $pdo->lastInsertId();

            // 写真保存
            if (isset($place['photos'][0]['photo_reference'])) {
                $photoRef = $place['photos'][0]['photo_reference'];
                $photoUrl = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photoreference={$photoRef}&key={$GOOGLE_MAPS_API_KEY}";
                $imgData = file_get_contents($photoUrl);
                if ($imgData) {
                    $uploadPath = BASE_PATH . "/upload/{$page_uid}/stores";
                    if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                    $original = imagecreatefromstring($imgData);
                    if ($original) {
                        $width = 600;
                        $height = (int)(imagesy($original) * ($width / imagesx($original)));
                        $resized = imagecreatetruecolor($width, $height);
                        imagecopyresampled($resized, $original, 0, 0, 0, 0, $width, $height, imagesx($original), imagesy($original));
                        imagejpeg($resized, "{$uploadPath}/{$uid}.jpg", 85);
                        imagedestroy($resized);
                        imagedestroy($original);
                    }
                }
            }

            $existing_place_ids[$place_id] = true;
            $inserted++;
            $savedCount++;

            // 取得数上限到達で break
            if ($inserted >= $limit) break;
        }
    }

    echo "<p>✅ {$savedCount} 件の店舗を登録しました。</p>";
}
