<?php include('../../compornents/php_header.php');

require_once dirname(__DIR__) . '/../core/guest_head.php';

require_once  dirname(__DIR__) . '/../core/category.php'; // APIキーを読み込む

// var_dump($category_list);
// exit;
$centerLat = 35.711892;
$centerLng = 139.857269;

$stmt = $pdo->prepare("SELECT base_data, geo_data FROM facility_ai_data WHERE page_uid = :page_uid LIMIT 1");
$stmt->execute([':page_uid' => $page_uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// geo_data の処理
$centerLat = 35.711892;
$centerLng = 139.857269;

if (!empty($row['geo_data'])) {
    $geo_data = json_decode($row['geo_data'], true);
    $centerLat = floatval($geo_data['緯度経度']['緯度'] ?? $geo_data['緯度'] ?? $centerLat);
    $centerLng = floatval($geo_data['緯度経度']['経度'] ?? $geo_data['経度'] ?? $centerLng);
}

$where = "facility_uid = :page_uid";
$params = [':page_uid' => $page_uid];

$cat = $_GET['cat'] ?? 'all';

if ($cat !== 'all') {
    $where .= " AND category = :cat";
    $params[':cat'] = $cat;
}
//
$stmt = $pdo->prepare("SELECT * FROM stores WHERE $where ORDER BY id ASC");
$stmt->execute($params);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="ja">
  <head>

    <?php include('../../compornents/head.php'); ?>
    <script type="text/javascript" src="/assets/js/jquery.bgswitcher.js"></script>
    <link rel="stylesheet" href="/assets/css/guest.css?id=<?= rand() ?>">
    <meta name="robots" content="noindex, nofollow">


    <script type="text/javascript">
      $(window).on("load", function() {
      });
    </script>


    <title>満竹華庵 - MANCHIKAN（まんちかん）｜新小岩に佇む、隠れ家の宿 - ゲストページ　- 周辺マップ</title>
    <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAtfrFG7bQDbr3DuW54UzIb2jsK17m1Me4"></script> -->
    <link rel="stylesheet" href="/assets/css/map.css?id=<?= rand() ?>">

    <style>
    .map-icon {
      background-color: black;
      color: white;
      border-radius: 50px;
      font-size: 30px;
      line-height: 35px;
      width: 35px;
      height: 35px;
      box-shadow: 2px 2px 5px rgba(0,0,0,0.4);
    }
    .map-icon::after {
      content: "";
      position: absolute;
      left: 50%;
      top: calc(100% - 1px);
      transform: translate(-50%, 0);
      width: 0;
      height: 0;
      border-left: 8px solid transparent;
      border-right: 8px solid transparent;
      border-top: 8px solid black;
    }

    .image_icon {
      border-radius: 50px;
      position: relative;
      /* overflow: hidden; */
      height: 50px;
      width: 50px;
    }

    .image_icon  img{
      border-radius: 50px;
      height: 50px;
      width: 50px;
      /* border: solid 2px; */
    }
    .image_icon::after {
      content: "";
      position: absolute;
      left: 50%;
      top: calc(100% - 0px);
      transform: translate(-50%, 0);
      width: 0;
      height: 0;
      border-left: 8px solid transparent;
      border-right: 8px solid transparent;
      border-top: 8px solid black;
    }

    <?php foreach ($category_list as $category): ?>
      .map-icon.<?= $category['icon'] ?> {
        background-color: <?= $category['color'] ?>;
      }
      .map-icon.<?= $category['icon'] ?>::after {
        border-top: 8px solid <?= $category['color'] ?>;
      }
      <?php endforeach; ?>

      .map-icon.home {
        border-radius: 50px;
        font-size: 35px;
        line-height: 50px;
        width: 50px;
        height: 50px;
      }
      .map-icon.home::after {
      }
    </style>
    <?php require_once dirname(__DIR__) . '/../core/guest_header.php'; ?>
  </head>
<body>

  <div id="app">

      <!-- Googleマップエリア -->
      <div class="map-container" :class="{ active : selectedStore}" id="map"></div>

      <form class="category-bar" method="GET">
        <!-- page_uid を常に送信 -->
        <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">

        <div id="category_scroll">
          <button v-for="cate in categories"
                  class="category_icon"
                  :class="{'active' : cate.id == getCate}"
                  name="cat"
                  :value="cate.id">
            <span class="material-symbols-outlined">{{ cate.icon }}</span>
            <span class="name">{{ lang == 'JP' ? cate.name : cate.en_name }}</span>
          </button>
        </div>

        <!-- 「すべて表示」ボタン -->
        <button class="category_icon first <?= $cat == 'all' ? 'active' : '' ?>"
                name="cat"
                value="all">
          <span class="material-symbols-outlined">task_alt</span>
          <span class="name">{{ lang == 'JP' ? '全て表示' : 'All' }}</span>
        </button>

        <div class="before_gradient"></div>
        <div class="after_gradient">
          <span class="material-symbols-outlined">arrow_right</span>
        </div>
      </form>

      <!-- 詳細エリア -->
      <div class="detail-container" :class="{ active: selectedStore, overwrap: selectedStore && selectedStore.overwrap }"
       :style="{
         top: position.y + 'px',
         height: windowHeight - position.y - 10 + 'px'
        }"
       >
        <div class="header">
          <h2 v-on:touchstart.self="startDrag" v-on:mouseDown.self="startDrag"  @click="detailClose()" >{{ selectedStore && selectedStore.name && lang == 'JP' ? selectedStore.name : '' }}{{ selectedStore && selectedStore.en_name && lang == 'EN' ? selectedStore.en_name : '' }}</h2>
          <span @click="detailClose" class="material-symbols-outlined">close</span>
          <a  v-if="selectedStore && selectedStore.lat" :href=" selectedStore.url ? selectedStore.url : 'https://maps.google.com/?q=' + selectedStore.lat + ',' + selectedStore.lng" target="_blank">
            <span @click="detailClose" class="material-symbols-outlined">open_in_new</span>
          </a>
          <!-- <img src="/assets/images/googlemap.png"> -->
        </div>
        <div class="point_wrap" :class=" mode ">
          <div v-if="selectedStore" class="point_bg_image"
               :style="{ backgroundImage: 'url(' + selectedImageUrl + '), url(/assets/images/no-image.png)' }">
          </div>

          <p v-if="lang == 'JP' " v-html="selectedStore && selectedStore.description ? selectedStore.description : ''"></p>
          <p v-if="lang == 'EN' " v-html="selectedStore && selectedStore.en_description ? selectedStore.en_description : ''"></p>
          <a class="map_link" v-if="selectedStore && selectedStore.lat" :href=" selectedStore.url ? selectedStore.url : 'https://maps.google.com/?q=' + selectedStore.lat + ',' + selectedStore.lng" target="_blank"><?=  l('Google Mapで開く', 'Open by Google Map') ?></a>
        </div>
      </div>
    </div>

  <script>

  const app = Vue.createApp({
      data() {
        return {
            stores: <?= isset($stores) ? json_encode($stores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]' ?>,
            categories: [],
            selectedCategory: 'all', // 初期状態は "すべて選択"
            selectedStore: null,
            map: null,
            markers: [],
            isDragging: false,
            position: { x: 0, y: window.innerHeight / 2 },
            offset: { x: 0, y: 0 },
            windowHeight: window.innerHeight,
            mode: 'half',
            dragStartY: '',
            getCate: "<?= isset($_GET['cat']) ? $_GET['cat'] : '' ?>",
            lang: "<?= $_SESSION['lang'] ?>",
            centerLat: <?= $centerLat ?>,
            centerLng: <?= $centerLng ?>,
            mapId: "4504f8b37365c3d0",
        }
      },
      computed: {
        selectedImageUrl() {
          if (!this.selectedStore || !this.selectedStore.uid) return null;

          // store画像の絶対パス
          return `/upload/${this.selectedStore.facility_uid}/stores/${this.selectedStore.uid}.jpg`;
        }
      },
      mounted() {
          this.initCategories();
          this.loadGoogleMaps();
      },
      methods: {
        startDrag(event) {
          this.isDragging = true;
          event.preventDefault();
          // console.log(event.clientY )

          // タッチイベントとマウスイベントでoffsetを設定
          if (event.type === 'mousedown') {
            this.offset.y = event.clientY - this.position.y;
            this.dragStartY = event.clientY

          } else if (event.type === 'touchstart') {
            this.offset.y = event.touches[0].clientY - this.position.y;
            this.dragStartY = event.touches[0].clientY;
          }



          // マウスの移動を追跡
          window.addEventListener('mousemove', this.onDrag);
          window.addEventListener('mouseup', this.stopDrag);

          // タッチの移動を追跡
          window.addEventListener('touchmove', this.onDrag);
          window.addEventListener('touchend', this.stopDrag);
        },
        onDrag(event) {
          if (this.isDragging) {
            // マウスイベントまたはタッチイベントによるY軸の移動のみ追跡
            if (event.type === 'mousemove') {
              this.position.y = event.clientY - this.offset.y;
              // console.log(event.clientY , this.offset.y)
            } else if (event.type === 'touchmove') {
              this.position.y = event.touches[0].clientY - this.offset.y;
            }
          }
        },
        stopDrag(event) {

          if (event.type == 'touchend') {


            // console.log(this.dragStartY  , event.changedTouches[0])

            if ( this.dragStartY  > event.changedTouches[0].clientY) {
              if (this.mode == 'half') {
                this.mode = 'all'
              }
            }

            if ( this.dragStartY  < event.changedTouches[0].clientY) {
              if (this.mode == 'half') {
                this.mode = 'close'
              } else if (this.mode == 'all' && this.dragStartY + 250 < event.changedTouches[0].clientY) {
                this.mode = 'close'
              }

              if (this.mode == 'all') {
                this.mode = 'half'
              }
            }

          } else {
            if ( this.dragStartY  > event.clientY) {
              if (this.mode == 'half') {
                this.mode = 'all'
              }
            }

            if ( this.dragStartY  < event.clientY) {
              if (this.mode == 'half') {
                this.mode = 'close'
              }
              if (this.mode == 'all' && this.dragStartY + 250 < event.clientY) {
                this.mode = 'close'
              }

              if (this.mode == 'all') {
                this.mode = 'half'
              }
            }
          }


          if (this.mode == 'close') {
            this.detailClose()
            this.position.y = window.innerHeight / 2
            this.mode = 'half'
          } else if (this.mode == 'all') {
            this.position.y = 0
          } else if (this.mode == 'half') {
            // this.detailClose()
            this.position.y = window.innerHeight / 2
          }



          this.isDragging = false;
          window.removeEventListener('mousemove', this.onDrag);
          window.removeEventListener('mouseup', this.stopDrag);
          window.removeEventListener('touchmove', this.onDrag);
          window.removeEventListener('touchend', this.stopDrag);
        },
        imageCheck (uid) {
          const img = new Image();
          img.src = '/upload/<?= $page_uid ?>/stores/' + uid + '.jpg';
          var that = this

          img.onload = function() {
            that.selectedStore.image = true
            return true
          };

          img.onerror = function() {
            that.selectedStore.image = false
            return false
          }
        },
          detailClose () {
            this.selectedStore = null
            this.mode == 'all'
            this.position.y = window.innerHeight / 2
          },
          initCategories() {
              // const uniqueCategories = new Map();
              // this.stores.forEach(store => {
              //     if (store.category_id && !uniqueCategories.has(store.category_id)) {
              //         uniqueCategories.set(store.category_id, { id: store.category_id, name: store.category_name, color: store.color });
              //     }
              // });
              const uniqueCategories = new Map();
              this.stores.forEach(store => {
                  if (store.category_id && !uniqueCategories.has(store.category_id)) {
                      uniqueCategories.set(store.category_id, store);

                  }
              });

              this.categories = <?php echo json_encode($category_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
          },
          loadGoogleMaps() {
              if (window.google && window.google.maps) {
                  this.initMap();
              } else {
                  const script = document.createElement("script");
                  script.src = "https://maps.googleapis.com/maps/api/js?language=<?= $_SESSION['lang'] == "JP" ? 'ja' : 'en' ?>&key=AIzaSyAtfrFG7bQDbr3DuW54UzIb2jsK17m1Me4&libraries=marker";
                  script.defer = true;
                  script.async = true;
                  script.onload = () => { this.initMap(); };
                  document.head.appendChild(script);
              }
          },
          initMap() {
              map = this.map = new google.maps.Map(document.getElementById("map"), {
                  center: {
                    lat: parseFloat(this.centerLat),
                    lng: parseFloat(this.centerLng)
                   },
                  zoom: 14,
                  minZoom: 13,  // 最小ズームレベル
                  maxZoom: 40,  // 最大ズームレベル
                  styles: [
                    {
                      featureType: 'poi',
                      visibility: 'off'
                    }
                  ],
                  mapId: this.mapId,
                  zoomControl: false,  // ズームコントロールを無効
                  mapTypeControl: false,  // 地図の種類（衛星画像など）切り替えボタンを無効
                  streetViewControl: false,  // ストリートビューコントロールを無効
                  fullscreenControl: false,  // フルスクリーンコントロールを無効
                  scaleControl: false  // スケールコントロールを無効
              });

              this.stores.forEach(store => this.addMarker(store));
              console.log(this.stores)


              const homeIconData = <?= json_encode($design['logo_base64'] ?? '') ?>;


              const markerContent = document.createElement("div");
              if (homeIconData) {
                markerContent.className = "image_icon "
                // base64アイコン画像を使う場合
                const img = document.createElement("img");
                img.src = "data:image/png;base64," + homeIconData;
                markerContent.appendChild(img);
              } else {
                // Googleアイコンを使う場合
                const homePin = document.createElement("span");
                homePin.className = "material-symbols-outlined map-icon home";
                homePin.textContent = "cottage";
                markerContent.appendChild(homePin);
              }



              const marker = new google.maps.marker.AdvancedMarkerElement({
                  map,
                  position: { lat: parseFloat('<?= $centerLat ?>'), lng: parseFloat('<?= $centerLng ?>') },
                  content: markerContent,
              });

          },
          addMarker(store) {

              if (!store.lat || !store.lng) return;
              const priceTag = document.createElement("span");


              var icon = this.categories[store.category].icon

              priceTag.className = "material-symbols-outlined map-icon " + icon;
              priceTag.textContent = icon;


              const marker = new google.maps.marker.AdvancedMarkerElement({
                  map,
                  position: { lat: parseFloat(store.lat), lng: parseFloat(store.lng) },
                  title: "施設",
                  content: priceTag,
              });
              marker.content.addEventListener("click", () => {
                  this.selectedStore = store;
                  this.imageCheck(store.uid);
                  this.recenterMap(marker);
              });
          },
          recenterMap(marker) {
            const scale = Math.pow(2, this.map.getZoom());
            const worldCoordinateCenter = this.map.getProjection().fromLatLngToPoint(marker.position);
            const pixelOffset = { x: 0, y: (window.innerHeight / 5) };

            const worldCoordinateNewCenter = new google.maps.Point(
                worldCoordinateCenter.x + pixelOffset.x / scale,
                worldCoordinateCenter.y + pixelOffset.y / scale
            );

            const newCenter = this.map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
            this.map.panTo(newCenter);
        }
      }
  });
  app.mount("#app");

  </script>





  <?php include('../../compornents/guest_footer.php'); ?>
  <?php include('../../compornents/menu.php'); ?>
</body>
</html>
