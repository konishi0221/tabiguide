<?php
require_once '../../db.php';
require_once '../../config.php'; // APIキーを読み込む

// 店舗データを取得
$store_result = $mysqli->query("SELECT stores.*, categories.name AS category_name, categories.color FROM stores
                                LEFT JOIN categories ON stores.category_id = categories.id
                                ORDER BY stores.id ASC");
$stores = $store_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>周辺マップ</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .category-bar { display: flex; gap: 10px; padding: 10px; overflow-x: auto; }
        .category-bar button { padding: 5px 10px; border: 1px solid #ccc; cursor: pointer; }
        /* カテゴリ選択時のハイライト */
        .category-bar button.active {
            border: 2px solid black;
            font-weight: bold;
        }

        .map-container { width: 100%; height: 400px; }
        .menu-bar { display: flex; justify-content: space-around; padding: 10px; border-top: 1px solid #ccc; }
        .menu-bar button { background: none; border: none; cursor: pointer; }
        .info-container { padding: 10px; font-size: 18px; font-weight: bold; }
        .detail-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            box-shadow: 0px -4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transform: translateY(100%);
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .detail-container.active {
            transform: translateY(0);
            opacity: 1;
        }

    </style>
</head>
<body>
    <div id="app">
        <!-- カテゴリ選択バー -->
        <div class="category-bar">
            <button @click="filterCategory('all')" :class="{ active: selectedCategory === 'all' }">全て選択</button>
            <button v-for="cat in categories" :key="cat.id" @click="filterCategory(cat.id)"
                    :class="{ active: selectedCategory === cat.id }" :style="{ backgroundColor: cat.color }">
                {{ cat.name }}
            </button>
        </div>

        <!-- Googleマップエリア -->
        <div class="map-container" id="map"></div>

        <!-- 詳細エリア -->
        <div class="detail-container" :class="{ active: selectedStore }" v-if="selectedStore">
            <h2>{{ selectedStore.name }}</h2>
            <p>{{ selectedStore.description }}</p>
            <a :href="'https://maps.google.com/?q=' + selectedStore.lat + ',' + selectedStore.lng" target="_blank">Google Map で開く</a>
            <button @click="selectedStore = null">×</button>
        </div>


        <!-- メニューバー -->
        <div class="menu-bar">
            <button>🏠 ホーム</button>
            <button>🗺 マップ</button>
            <button>💬 AIチャット</button>
        </div>
    </div>

    <script>
    const app = Vue.createApp({
        data() {
          return {
              stores: <?php echo json_encode($stores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
              categories: [],
              selectedCategory: 'all', // 初期状態は "すべて選択"
              selectedStore: null,
              map: null,
              markers: []
          }
        },
        mounted() {
            this.initCategories();
            this.loadGoogleMaps();
        },
        methods: {
            initCategories() {
                const uniqueCategories = new Map();
                this.stores.forEach(store => {
                    if (store.category_id && !uniqueCategories.has(store.category_id)) {
                        uniqueCategories.set(store.category_id, { id: store.category_id, name: store.category_name, color: store.color });
                    }
                });
                this.categories = Array.from(uniqueCategories.values());
            },
            loadGoogleMaps() {
                if (window.google && window.google.maps) {
                    this.initMap();
                } else {
                    const script = document.createElement("script");
                    script.src = "https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>";
                    script.defer = true;
                    script.async = true;
                    script.onload = () => { this.initMap(); };
                    document.head.appendChild(script);
                }
            },
            initMap() {
                this.map = new google.maps.Map(document.getElementById("map"), {
                    center: { lat: 35.711892, lng: 139.857269 },
                    zoom: 14
                });
                this.stores.forEach(store => this.addMarker(store));
            },
            addMarker(store) {
                if (!store.lat || !store.lng) return;
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(store.lat), lng: parseFloat(store.lng) },
                    map: this.map,
                    title: store.name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: store.color || "red",
                        fillOpacity: 1,
                        strokeWeight: 1
                    }
                });

                marker.addListener("click", () => {
                    this.selectedStore = store;
                    console.log("ピンがクリックされた:", this.selectedStore);
                });

                this.markers.push(marker); // ここで明示的に追加！
            },
            filterCategory(categoryId) {
                this.selectedCategory = categoryId;  // 選択したカテゴリを更新

                console.log("削除前のマーカー数:", this.markers.length);

                // マーカーを削除
                this.markers.forEach(marker => marker.setMap(null));
                this.markers = [];  // Vueのデータもリセット

                console.log("削除後のマーカー数:", this.markers.length);

                console.log("選択したカテゴリ:", categoryId);

                // Google Map を再初期化
                document.getElementById("map").innerHTML = "";  // マップをクリア
                this.initMap();

                // フィルター後の店舗リストを作成
                const filteredStores = categoryId === 'all'
                    ? this.stores
                    : this.stores.filter(store => Number(store.category_id) === Number(categoryId));

                console.log("フィルター後の店舗:", filteredStores);

                // フィルター後の店舗のみマーカーを追加
                filteredStores.forEach(store => {
                    if (!store || !store.lat || !store.lng) {
                        console.error("無効な店舗データ:", store);
                        return;
                    }
                    this.addMarker(store);
                });

                console.log("マーカー更新完了。現在のマーカー:", this.markers);
            }
        }
    });
    app.mount("#app");
    </script>
</body>
</html>
