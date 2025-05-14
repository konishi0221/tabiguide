<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}


require_once BASE_PATH . '/core/db.php';
require_once BASE_PATH . '/core/config.php';
require_once BASE_PATH . '/core/category.php';
require_once BASE_PATH . '/core/token_usage.php';

function previewNearbyStores($page_uid) {
    global $pdo, $GOOGLE_MAPS_API_KEY, $category_list;

    // ramenカテゴリを除外
    // unset($category_list['ramen']);

    // geo_data 取得
    $stmt = $pdo->prepare("SELECT geo_data FROM facility_ai_data WHERE page_uid = ?");
    $stmt->execute([$page_uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "施設が見つかりません。";
        return;
    }

    $geo = json_decode($row['geo_data'] ?? '{}', true);
    $geo = $geo['緯度経度'];
    $lat = $geo['緯度'] ?? null;
    $lng = $geo['経度'] ?? null;
    if (!$lat || !$lng) {
        echo "緯度経度が不正です。";
        return;
    }

    // Google Place API type 対応表
    $keyToGoogleType = [
        'tour'       => 'tourist_attraction',
        'conveni'    => 'convenience_store',
        'essentials' => 'supermarket',
        'laundry'    => 'laundry',
        'parking'    => 'parking',
        'restaurant' => 'restaurant',
        'cafe'       => 'cafe',
        'other'      => 'store',
    ];

    echo "<h1>📍「{$page_uid}」の周辺施設候補</h1>";

    $mapsLoads = 0;

    foreach ($category_list as $key => $info) {
        $googleType = $keyToGoogleType[$key] ?? null;
        if (!$googleType) continue;

        $radius = 1000;
        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&type={$googleType}&language=ja&key={$GOOGLE_MAPS_API_KEY}";
        $res = json_decode(file_get_contents($url), true);
        $mapsLoads++;

        echo "<h2>{$info['name']} ({$key})</h2>";

        if ($res['status'] !== 'OK') {
            echo "<p>取得失敗: {$res['status']}</p>";
            continue;
        }

        echo "<ul style='list-style:none;padding:0'>";
        $count = 0;
        foreach ($res['results'] as $place) {
            if (++$count > 5) break;

            $name = $place['name'] ?? '不明';
            $vicinity = $place['vicinity'] ?? '住所不明';
            $placeLat = $place['geometry']['location']['lat'] ?? '';
            $placeLng = $place['geometry']['location']['lng'] ?? '';
            $photoUrl = '';

            // 重複チェック
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE facility_uid = ? AND category = ? AND name = ?");
            $stmt2->execute([$page_uid, $key, $name]);
            $exists = $stmt2->fetchColumn() > 0;

            $rating = $place['rating'] ?? null;
            $reviewCount = $place['user_ratings_total'] ?? null;
            $status = $place['business_status'] ?? null;
            $openNow = $place['opening_hours']['open_now'] ?? null;

            $descriptionParts = [];
            if ($rating) $descriptionParts[] = "⭐ {$rating}（{$reviewCount}件）";
            if ($status === 'OPERATIONAL') $descriptionParts[] = "営業中";
            if (isset($openNow)) $descriptionParts[] = $openNow ? "現在営業中" : "現在休業中";

            $descriptionText = implode('・', $descriptionParts);


            // 写真取得（あれば）
            if (isset($place['photos'][0]['photo_reference'])) {
                $photoRef = $place['photos'][0]['photo_reference'];
                $photoUrl = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photoreference={$photoRef}&key={$GOOGLE_MAPS_API_KEY}";
            }

            // 表示
            echo "<li style='margin-bottom:10px;padding:10px;border:1px solid #ddd;border-radius:8px;'>";
            if ($photoUrl) {
                echo "<img src='{$photoUrl}' style='width:120px;border-radius:6px;box-shadow:0 2px 4px rgba(0,0,0,0.2);'><br>";
            }
            echo "<strong>{$name}</strong><br>";
            echo "📍 <small>{$vicinity}</small><br>";
            echo "緯度: {$placeLat} / 経度: {$placeLng}<br>";
            echo "カテゴリ: {$key}<br>";
            echo $exists
                ? "<span style='color:gray;'>※ 登録済み</span>"
                : "<span style='color:green;'>✅ 登録候補</span>";
            if ($descriptionText) {
                echo "<div style='font-size:13px;color:#555;'>説明: {$descriptionText}</div>";
            }
            echo "</li>";
        }
        echo "</ul>";
    }

    // ---- Google Maps API cost accounting ----
    if (function_exists('chargeGoogleMaps') && $mapsLoads > 0) {
        chargeGoogleMaps($page_uid, $mapsLoads);
    }
}
