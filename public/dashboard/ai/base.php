<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$stmt = $pdo->prepare("SELECT * FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    echo "この施設のデータが存在しないか、あなたに編集権限がありません。";
    exit;
}

function safe_json_decode($json) {
    return (!empty($json) && is_string($json)) ? json_decode($json, true) : [];
}

$base_data       = safe_json_decode($data['base_data']);
$geo_data        = safe_json_decode($data['geo_data']);
$amenities_data  = safe_json_decode($data['amenities_data']);
$rule_data       = safe_json_decode($data['rule_data']);
$location_data   = safe_json_decode($data['location_data']);
$services_data   = safe_json_decode($data['services_data']);
$stay_data=  safe_json_decode($data['stay_data']);
$contact_data=  safe_json_decode($data['contact_data']);




$last_tab = $_POST['last_tab'] ?? $_GET['last_tab'] ?? 'base_data';



require_once __DIR__ . '/../../core/facility_template.php';

$facility_type = $base_data['施設タイプ'] ?? 'minpaku';
$template = loadFacilityTemplate($facility_type);

$sections = [
    [ 'label' => '基本情報',       'key' => 'base_data',       'data' => $base_data,      'template' => $template['基本情報'] ?? [] ],
    [ 'label' => '緯度経度',       'key' => 'geo_data',        'data' => $geo_data,       'template' => $template['緯度経度'] ?? [] ],
    [ 'label' => '宿泊情報',       'key' => 'stay_data',        'data' => $stay_data,       'template' => $template['宿泊情報'] ?? [] ],
    [ 'label' => '連絡先',       'key' => 'contact_data',        'data' => $contact_data,       'template' => $template['連絡先'] ?? [] ],
    [ 'label' => '設備・アメニティ', 'key' => 'amenities_data',  'data' => $amenities_data, 'template' => $template['設備・アメニティ'] ?? [] ],
    [ 'label' => 'ルール・禁止事項', 'key' => 'rule_data',       'data' => $rule_data,      'template' => $template['ルール・禁止事項'] ?? [] ],
    [ 'label' => '周辺情報',       'key' => 'location_data',   'data' => $location_data,  'template' => $template['周辺情報'] ?? [] ],
    [ 'label' => 'サービス',       'key' => 'services_data',   'data' => $services_data,  'template' => $template['サービス'] ?? [] ],
];



if (!$template) {
    die("❌ JSONの読み込みに失敗しました。中身を確認してください。");
}



$page_uid = $_GET['page_uid'] ?? null;
if (!$page_uid) {
    echo "ページUIDが指定されていません。";
    exit;
}

if (!$data) {
    echo "この施設のデータが存在しないか、あなたに編集権限がありません。";
    exit;
}
// $sections = array_map(function ($s) use ($template) {
//   return [
//     'label' => $s['label'],
//     'key'   => $s['key'],
//     'data'  => is_array($s['data']) ? $s['data'] : [],
//     'template' => $template[$s['label']] ?? []
//   ];
// }, $sections);


?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>施設基本情報</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&libraries=marker&v=beta"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js" type="module">
  import ComponentInput from './component-input.js'
  </script> -->

  <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GOOGLE_MAPS_API_KEY; ?>&libraries=marker&v=beta&callback=initMap"></script> -->
</head>


<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>


  <div class="dashboard-container">

  <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

  <div id="app" class="container">
    <main>

    <h1>施設基本情報</h1>

     <div class="tab-nav">
       <button
         v-for="section in sections"
         @click="activeTabKey = section.key"
         :class="{ active: activeTabKey === section.key }"
         type="button"
       >{{ section.label }}</button>
     </div>
     <div v-show="activeTabKey === 'geo_data' " ref="mapContainer" id="map" style="width: 100%; height: 300px;"></div>

     <form method="POST" action="update.php">
       <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">
       <input type="hidden" name="last_tab" :value="activeTabKey">

       <!-- セクションごとの描画 -->
       <!-- <div>{{ template['基本情報'] }}</div> -->
       <div
         v-for="section in sections"
         :key="section.key"
         v-show="activeTabKey === section.key"
       >

         <fieldset>


           <component-input
           v-for="(schema, key) in section.template"
            :placeholder="schema.placeholder || ''"
             :label="key"
             :name="`${section.key}[${key}]`"
             :schema="schema"
             :model_value="section.data[key]"
             @update:model_value="val => section.data[key] = val"
           />


         </fieldset>
       </div>

       <!-- hiddenで各セクションのJSONをまとめて送信 -->
       <input
         v-for="section in sections"
         :key="section.key"
         :name="sectionToKey(section)"
         :value="JSON.stringify(section.data)"
         type="hidden"
       >

       <button type="submit" class="save-button">保存する</button>
     </form>

  </main>
  </div>
</div>

<script type="module">
import { createApp } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
import ComponentInput from './component-input.js';

// import ComponentInput from './component-input.js'

const app = Vue.createApp({
  data() {
    return {
      activeTabKey: "<?= htmlspecialchars($last_tab, ENT_QUOTES, 'UTF-8') ?>",
      sections: <?= json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>,
      map: null,
      marker: null,
      latInput: null,
      lngInput: null
    };
  },
  mounted() {
    this.initMap();
    // if (this.activeTabKey === 'geo_data') {
    //   this.initMap();
    // }
  },
  created() {
  this.sections.forEach(section => {
    if (!section.data) section.data = {};
    Object.keys(section.template).forEach(key => {
      if (!(key in section.data)) {
        const schema = section.template[key];

        if (Array.isArray(schema)) {
          // セレクト or CRUD型
            /* CRUD（object 配列）なら必ず [] をセット、
               文字列配列（select）なら空文字 */
            section.data[key] = (schema.length === 0 || typeof schema[0] === 'object')
              ? []
              : '';
        } else if (typeof schema === 'object' && schema !== null && 'value' in schema && 'note' in schema) {
          section.data[key] = { value: false, note: '' };
        } else if (typeof schema === 'boolean') {
          section.data[key] = false;
        } else {
          section.data[key] = '';
        }
      }
    });
  });
},
  watch: {
    activeTabKey(newVal) {
      if (newVal === 'geo_data') {
        setTimeout(() => {
          this.initMap();
        }, 200); // DOM描画待ち
      }
    }
  },
  methods: {
    sectionToKey(label) {
      const map = {
        "基本情報": "base_data",
        "緯度経度": "geo_data",
        "設備・アメニティ": "amenities_data",
        "ルール・禁止事項": "rule_data",
        "周辺情報": "location_data",
        "サービス": "services_data"
      };
      return map[label] || label;
    },
    getLabel(fieldName, value) {
      // フィールド名ごとのマッピング
      const labelMap = {
        'base_data[施設タイプ]': {
          camp: 'キャンプ場',
          hotel: 'ホテル',
          ryokan: '旅館',
          guesthouse: 'ゲストハウス',
          villa: '貸別荘',
          event: 'イベント会場'
        },
        // 他の select にも拡張可能
      };

      return labelMap[fieldName]?.[value] ?? value;
    },
    initMap() {
      // 対象セクション探す
      const geoSection = this.sections.find(s => s.key === 'geo_data');
      if (!geoSection) return;

      const lat = parseFloat(geoSection.data?.["緯度"] || 35.6895);
      const lng = parseFloat(geoSection.data?.["経度"] || 139.6917);

      var map = this.map = new google.maps.Map(this.$refs.mapContainer, {
        center: { lat, lng },
        zoom: 14,
        mapId: "DEMO_MAP_ID"
      });

      const { AdvancedMarkerElement, PinElement } = google.maps.marker;

      const pin = new PinElement({
        background: "#DB4437",
        borderColor: "#A52714",
        glyphColor: "#fff"
      });

      this.marker = new AdvancedMarkerElement({
        map: map,
        position: { lat, lng },
        content: pin.element,
        title: "位置をドラッグで変更できます",
      });

      this.map.addListener("click", (e) => {
        const newLat = e.latLng.lat();
        const newLng = e.latLng.lng();

        geoSection.data["緯度"] = newLat;
        geoSection.data["経度"] = newLng;

        this.marker.position = new google.maps.LatLng(newLat, newLng);
      });
    }
  }
});

app.component('component-input', ComponentInput);
app.mount("#app");

</script>

</body>
</html>
