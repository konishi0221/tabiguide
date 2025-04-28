<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? null;


if (!$page_uid) {
    echo "施設が選択されていません。";
    exit;
}

$centerLat = 35.658034;
$centerLng = 139.701636;


if ($page_uid) {
    $stmt = $pdo->prepare("SELECT geo_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
    $stmt->execute([$page_uid]);
    $ai_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ai_data && !empty($ai_data['geo_data'])) {
        $geo = json_decode($ai_data['geo_data'], true);
        if (!empty($geo['緯度'])) {
            $initialLat = floatval($geo['緯度']);
        }
        if (!empty($geo['経度'])) {
            $initialLng = floatval($geo['経度']);
        }
    }
}

// 初期データ
$store = [
    'id' => '',
    'name' => '',
    'category' => '',
    'lat' => $initialLat,  // デフォルトの中心座標
    'lng' => $initialLng,
    'description' => '',
    'is_visible' => 1,
    'uid' => '',
    'url' => '',
    'en_description' => '',
    'en_name' => '',
    'facility_uid' => $page_uid,
];

// 編集時の処理
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM stores WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $store = $data ?: $store; // データが取得できなかった場合、元の $store を使用
}

// dd( $_SERVER['DOCUMENT_ROOT'])
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>店舗編集</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&libraries=marker"></script>
    <link rel="stylesheet" href="/assets/css/admin_layout.css">
    <link rel="stylesheet" href="/assets/css/admin_design.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

  <div class="dashboard-container">
    <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>
    <div id="app">
      <main>
        <h1>店舗編集</h1>

        <div id="map" style="height: 500px;"></div>

        <form action="complete.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo !empty($store['id']) ? $store['id'] : ''; ?>">
            <input type="hidden" name="uid" v-model="uid">
            <input type="hidden" name="mode" value="<?php echo empty($store['id']) ? 'insert' : 'update'; ?>">
            <input type="hidden" name="facility_uid" v-model="facility_uid" >

            <p><label>店舗名: </label><input type="text" name="name" v-model="name"> <span @click="translate('name')" >翻訳</span></p>
            <!-- <p>
              <label>店舗名(英語): </label>
              <input type="text" v-model="en_name" @input="encodeForPost(en_name, 'en_name_encoded')">
              <input type="hidden" name="en_name" :value="en_name_encoded">
            </p> -->
            <p><label>カテゴリ:</label>
                <select name="category" v-model="category">
                    <?php foreach ($category_list as $category) { ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php } ?>
                </select>
            </p>

            <meta name="robots" content="noindex, nofollow">
            <p>
              <label>URL:</label>
              <input type="text" v-model="url" @input="encodeForPost(url, 'url_encoded')">
              <input type="hidden" name="url" :value="url_encoded">
            </p>


            <p><label>説明: </label><textarea name="description" v-model="description"></textarea>  <span @click="translate('description')" >翻訳</span></p>
            <!-- <p>
              <label>説明(英語): </label>
              <textarea v-model="en_description" @input="encodeForPost(en_description, 'en_description_encoded')"></textarea>
              <input type="hidden" name="en_description" :value="en_description_encoded">
            </p> -->

            <p><label>緯度: </label>{{ lat }} <input type="hidden" name="lat" v-model="lat"></p>
            <p><label>経度: </label>{{ lng }} <input type="hidden" name="lng" v-model="lng"></p>
            <p><label>表示:</label>
                <select name="is_visible" v-model="is_visible">
                    <option value="1">表示</option>
                    <option value="0">非表示</option>
                </select>
            </p>
            <p><label>画像:</label>
                <input type="file" name="image">
                <br>
            </p>

            <button type="submit">{{ id ? '更新' : '追加' }}</button>
        </form>

        <?php
        $imagePath = "../../assets/uploads/" . ($store['uid'] ?? '') . ".jpg";
        ?>
          <br>
          <br>
          <br>
          <br>
          <div id="image-preview" v-if="uid">
            <img :src="src"
                 alt="店舗画像"
                 onerror="this.onerror=null;this.src='/assets/images/no_image.png';">
            <br><br>

            <form action="delete_store.php" method="POST">
                <input type="hidden" name="mode" value="delete_image">
                <input type="hidden" name="uid" value="<?= htmlspecialchars($store['uid']) ?>">
                <input type="hidden" name="id" value="<?= $store['id'] ?>">
                <input type="hidden" name="facility_uid" value="<?= $store['facility_uid'] ?? $_GET['page_uid'] ?>">
                <button type="submit" onclick="return confirm('画像を削除しますか？');">画像を削除</button>
            </form>

          </div>
      </main>
    </div>
  </div>

  <style>
    #image-preview img {
      width: calc(200px);
      border-radius: 5px;
      border: solid 1px #b6b6b6;
    }
  </style>

    <script>
    const app = Vue.createApp({
        data() {
          return {
            id: <?php echo json_encode($store['id'] ?? ''); ?>,
            name: <?php echo json_encode($store['name'] ?? ''); ?>,
            en_name: <?php echo json_encode($store['en_name'] ?? ''); ?>,
            category: <?php echo json_encode($store['category'] ?? ''); ?>,
            lat: <?php echo json_encode((string)($store['lat'] ?? $initialLat)); ?>,
            lng: <?php echo json_encode((string)($store['lng'] ?? $initialLng)); ?>,
            description: <?php echo json_encode($store['description'] ?? ''); ?>,
            en_description: <?php echo json_encode($store['en_description'] ?? ''); ?>,
            is_visible: <?php echo json_encode((string)($store['is_visible'] ?? '1')); ?>,
            uid: <?php echo json_encode($store['uid'] ?? ''); ?>,
            url: <?php echo json_encode($store['url'] ?? ''); ?>,
            facility_uid: <?php echo json_encode($store['facility_uid'] ?? $page_uid); ?>,
            src: "/upload/" + <?php echo json_encode($store['facility_uid'] ?? $page_uid); ?> + "/stores/" + <?php echo json_encode($store['uid'] ?? ''); ?> + ".jpg",
            map: null,
            marker: null,
            en_description_encoded: '',
            en_name_encoded: '',
            url_encoded: '',
          };
        },
        mounted() {
          this.encodeForPost(this.en_description, 'en_description_encoded')
          this.encodeForPost(this.en_name, 'en_name_encoded')
          this.encodeForPost(this.url, 'url_encoded')

          window.onload = () => {
            this.loadGoogleMaps().then(() => {
                this.initMap();
            }).catch(err => {
                console.error("Google Maps APIの読み込みに失敗しました", err);
            });
          }
        },
        watch: {
            lat(newVal) {
                if (this.marker) {
                  this.marker.position = new google.maps.LatLng(parseFloat(newVal), parseFloat(this.lng));
                }
            },
            lng(newVal) {
                if (this.marker) {
                  this.marker.position = new google.maps.LatLng(parseFloat(this.lat), parseFloat(newVal));
                }
            }
        },
        methods: {
          encodeForPost(value, target) {
            try {
              this[target] = btoa(encodeURIComponent(value));
            } catch (e) {
              console.error("エンコード失敗:", e);
              this[target] = '';
            }
          },
          translate(field) {
              let sourceText = '';
              let targetField = '';

              if (field === 'name') {
                  sourceText = this.name;
                  targetField = 'en_name';
              } else if (field === 'description') {
                  sourceText = this.description;
                  targetField = 'en_description';
              } else {
                  console.warn('未対応のフィールド:', field);
                  return;
              }

              if (!sourceText) {
                  alert('翻訳するテキストが空です');
                  return;
              }

              const apiKey = "<?php echo $GOOGLE_MAPS_API_KEY; ?>";
              const url = "https://translation.googleapis.com/language/translate/v2";

              axios.post(url, {
                  q: sourceText,
                  source: 'ja',
                  target: 'en',
                  format: 'text'
              }, {
                  params: { key: apiKey }
              })
              .then(res => {
                  const translated = res.data?.data?.translations?.[0]?.translatedText;
                  if (translated) {
                      this[targetField] = translated;

                      const encodedField = targetField + '_encoded';
                      this.encodeForPost(translated, encodedField);

                  } else {
                      alert("翻訳に失敗しました");
                  }
              })
              .catch(err => {
                  console.error("翻訳エラー:", err);
                  alert("通信エラーが発生しました");
              });
          },
            loadGoogleMaps() {
                return new Promise((resolve, reject) => {
                    if (window.google && window.google.maps) {
                        resolve();
                    } else {
                        const script = document.createElement("script");
                        script.src = "https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&callback=initGoogleMaps";
                        script.defer = true;
                        script.async = true;
                        script.onerror = reject;
                        document.head.appendChild(script);
                        window.initGoogleMaps = () => resolve();
                    }
                });
            },
            initMap() {
                if (!window.google || !window.google.maps) {
                    console.error("Google Maps API is not loaded yet.");
                    return;
                }


                map = this.map = new google.maps.Map(document.getElementById("map"), {
                    center: { lat: parseFloat(this.lat), lng: parseFloat(this.lng) },
                    zoom: 14,
                    mapId: "4504f8b37365c3d0"
                });

                const marker = new google.maps.marker.AdvancedMarkerElement({
                      position: { lat: parseFloat(this.lat), lng: parseFloat(this.lng) },
                      map,
                      draggable: true,
                });

                marker.addListener("dragend", (event) => {
                    this.lat = event.latLng.lat().toFixed(7);
                    this.lng = event.latLng.lng().toFixed(7);
                });
                // 座標更新イベント追加！！
                marker.addListener("dragend", (event) => {
                    this.lat = event.latLng.lat().toFixed(7);
                    this.lng = event.latLng.lng().toFixed(7);
                });

                // マップクリック時にもピン移動
                this.map.addListener("click", (event) => {
                    this.lat = event.latLng.lat().toFixed(7);
                    this.lng = event.latLng.lng().toFixed(7);
                    marker.position = event.latLng;
                });

                this.marker = marker;


            },
        }
    });

    app.mount("#app");
    </script>
</body>
</html>
