<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(__DIR__) . '/stores/get_stores.php';

$facility_uid = $_GET['page_uid'] ?? null;

if (!$facility_uid) {
    echo "施設UIDが指定されていません";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM stores WHERE facility_uid = :facility_uid ORDER BY id ASC");
$stmt->execute([':facility_uid' => $facility_uid]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$centerLat = 35.658034;
$centerLng = 139.701636;


$stmt = $pdo->prepare("SELECT geo_data FROM facility_ai_data WHERE page_uid = :page_uid LIMIT 1");
$stmt->execute([':page_uid' => $facility_uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && !empty($row['geo_data'])) {
    $geo = json_decode($row['geo_data'], true);
    if (!empty($geo['緯度'])) {
        $centerLat = floatval($geo['緯度']);
    }
    if (!empty($geo['経度'])) {
        $centerLng = floatval($geo['経度']);
    }
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST']; // localhost:8080 含む
    return "$protocol://$host";
}


foreach ($stores as &$store) {
    $imgPath = __DIR__ . "/../../upload/{$facility_uid}/stores/{$store['uid']}.jpg";
    $store['has_image'] = file_exists($imgPath);
}
unset($store); // foreach参照を解放

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>店舗一覧</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&libraries=marker"></script>
    <link rel="stylesheet" href="/assets/css/admin_layout.css">
    <link rel="stylesheet" href="/assets/css/admin_design.css">
    <meta name="robots" content="noindex, nofollow">



    <style>
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 16px;
    }

    .card {
      position: relative;
      background: #fff;
      padding: 12px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: box-shadow 0.2s;
    }

    .card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .icon-circle img {
      width: 100%;
      height: 240px;
      object-fit: cover;
      border-radius: 8px;
      background: #eee;
    }

    .card h3 {
      font-size: 16px;
      margin-top: 10px;
    }

    .card p {
      font-size: 14px;
      color: #555;
    }

    form.delete-button {
      position: absolute;
      bottom: 10px;
      right: 10px;
      border: none;
      border-radius: 50px;
      padding: 4px;
    }
    form.delete-button button {
      border-radius: 50px;
      background-color: white ;
      padding: 0;
    }
    .delete-button:hover {
      background-color: rgba(0, 0, 0, 0.2);
    }

    .delete-button .material-symbols-outlined {
      color: #888;
      font-size: 20px;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
      margin: 20px 0;
    }

    a.create,
    button.batch-create
    {
      padding: 8px 14px;
      font-size: 14px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    a.create {
      background-color: #007bff;
      color: #fff;
    }

    a.create:hover {
      background-color: #0056b3;
    }

    button.batch-create {
      background-color: #3498db;
      color: white;
    }

    button.batch-create:hover {
      background-color: #2980b9;
    }
    .empty-message {
      background: #f5f5f5;
      border: 2px dashed #ccc;
      border-radius: 12px;
      padding: 40px;
      text-align: center;
      color: #666;
      margin-top: 30px;
    }

    .empty-message .material-symbols-outlined {
      font-size: 48px;
      color: #bbb;
      margin-bottom: 12px;
    }



    </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

  <div class="dashboard-container">

    <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>
    <div id="app">
      <main>

        <h1>店舗一覧</h1>

        <div id="map" style="width: 100%; height: 500px;"></div>
        <a class="create" href="index.php?page_uid=<?= $facility_uid ?>">新規追加</a>


<!-- <form action="delete_all_stores.php" method="POST" onsubmit="return confirm('本当にすべての店舗を削除しますか？');">
  <input type="hidden" name="page_uid" value="<?= htmlspecialchars($facility_uid) ?>">
  <button type="submit" style="background: #e74c3c; color: white; padding: 8px 12px; border: none; border-radius: 4px;">店舗をすべて削除</button>
</form> -->



      <div class="store-list" v-if="Object(stores).length !== 0 " >
        <div class="card-grid">
          <div
            v-for="store in stores"
            :key="store.id"
            class="card"
            @click="editStore(store.id)"
          >
            <div class="icon-circle">
              <img
                :src="store.has_image ? '/upload/' + store.facility_uid + '/stores/' + store.uid + '.jpg' : '/assets/images/no_image.png'"
                class="icon"
              />
            </div>
            <h3>{{ store.name }}</h3>
            <p>{{ store.description }}</p>

            <form method="POST" action="/dashboard/stores/delete_store.php" class="delete-button" @click.stop>
              <input type="hidden" name="id" :value="store.id">
              <input type="hidden" name="uid" :value="store.uid">
              <input type="hidden" name="facility_uid" :value="store.facility_uid">
              <input type="hidden" name="page_uid" value="<?= htmlspecialchars($_GET['page_uid']) ?>">

              <button type="submit" onclick="return confirm('本当に削除しますか？');">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </form>

          </div>
        </div>
      </div>

      <div v-if="stores.length === 0" class="empty-message">
        <span class="material-symbols-outlined">storefront</span>
        <p>まだ店舗が登録されていません。</p>
        <p>「新規追加」または「店舗一括作成」から登録してください。</p>
        <form  action="get_stores_complete.php" method="post">
          <button name="page_uid" value="<?= htmlspecialchars($_GET['page_uid']) ?>">店舗一括作成</button>
        </form>

      </div>


      </main>
      </div>
    </div>

    <script>
    const app = Vue.createApp({
        data() {
            return {
                map: null,
                markers: [],
                infoWindow: null,
                stores: <?php echo json_encode($stores); ?>,
                mapId: "4504f8b37365c3d0",
                has_image: ''
            };
        },
        mounted() {
          window.onload = () => {
            this.loadGoogleMaps();
          }
        },
        computed: {
          getImageUrl(store) {
            return (store) => {
                return store.has_image
              ? '/upload/${store.facility_uid}/stores/${store.uid}.jpg'

              : '/assets/images/no_image.png';
            }
          }
        },
        methods: {
            loadGoogleMaps() {
                if (window.google && window.google.maps) {
                    this.initMap();
                } else {
                    const script = document.createElement("script");
                    script.src = "https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&libraries=marker";
                    script.defer = true;
                    script.async = true;
                    script.onload = () => {
                        this.initMap();
                    };
                    document.head.appendChild(script);
                }
            },
            editStore(id) {
              window.location.href = '/dashboard/stores/index.php?id=' + id + '&page_uid=' + '<?= htmlspecialchars($_GET['page_uid']) ?>';
            },
            initMap() {
                map = this.map = new google.maps.Map(document.getElementById("map"), {
                    center:{ lat: <?= $centerLat ?>, lng:  <?= $centerLng ?> },
                    zoom: 14,
                    mapId: this.mapId
                });

                this.infoWindow = new google.maps.InfoWindow();

                this.stores.forEach(store => {
                    this.addMarker(store);
                });
                this.addMainMarker()
            },
            addMarker(store) {
              lat = Number(store.lat)
              lng = Number(store.lng)

              const marker = new google.maps.marker.AdvancedMarkerElement({
                map,
                position: { lat: lat, lng: lng },
              });
            },
            addMainMarker() {
              const pinScaled = new google.maps.marker.PinElement({
                glyphColor: "#ff8300",
                background: "#FFD514",
                borderColor: "#ff8300",
              });

              const marker = new google.maps.marker.AdvancedMarkerElement({
                map,
                position: { lat: <?= $centerLat ?>, lng: <?= $centerLng ?> },
                content:pinScaled.element
              });
            }
        }
    });

    app.mount("#app");
    </script>
</body>
</html>
