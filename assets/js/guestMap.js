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
