<?php

require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
if (!$page_uid) {
    echo "page_uid is required";
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>館内マップ管理</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app">

<main id="mapApp">
  <h1>施設画像</h1>

  <div class="setting-section">
  <h2>マップ画像</h2>

    <p>画像をクリックしてアップロード（最大 3 枚）</p>

    <ul class="map-list">
            <li v-for="i in maxMaps" :key="i">
            <a v-if="hasImage(i)" :href="maps[i].url" target="_blank">
                <img :src="maps[i].displayUrl" :alt="i + '.jpg'" @click.prevent="triggerFile(i)">
            </a>
            <img v-else :src="getDisplayUrl(i)" :alt="i + '.jpg'" @click="triggerFile(i)">
            <input type="file" :ref="'file'+i" accept="image/*" style="display:none" @change="handleFile(i, $event)">
            <span>画像{{ i }}</span>
            <button v-if="hasImage(i)" @click="del(i)">削除</button>
            </li>
        </ul>
    </div>
  <div class="setting-section">
    <h2>施設イメージ</h2>
    <p>画像をクリックしてアップロード</p>
    <div class="facility-card">
      <a v-if="cover.has" :href="cover.url" target="_blank">
        <img :src="cover.displayUrl" alt="facility.jpg" @click.prevent="triggerCover">
      </a>
      <img v-else src="/assets/images/no_image.png" alt="facility.jpg" @click="triggerCover">
      <input type="file" ref="coverFile" accept="image/*" style="display:none" @change="handleCover">
      <span>施設の画像</span>
      <button v-if="cover.has" @click="delCover">削除</button>
    </div>
  </div>
</main>

<style>
ul.map-list{
  margin:1rem 0;
  padding:0;
  list-style:none;
  display:flex;
  flex-wrap:wrap;
  gap:16px;
}
ul.map-list li{
  width:180px;
  height:260px;
  border:1px solid #ccc;
  padding:8px;
  box-sizing:border-box;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:space-between;
  text-align:center;
}
ul.map-list img{
  width:160px;
  height:160px;
  object-fit:cover;
  display:block;
  cursor:pointer;
  border:1px solid #eee;
}
ul.map-list span{
  font-size:0.9rem;
  margin-top:4px;
}
ul.map-list button{
  padding:4px 12px;
  font-size:0.8rem;
  cursor:pointer;
}
.facility-card{
  width:220px;
  border:1px solid #ccc;
  padding:8px;
  text-align:center;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:8px;
}
.facility-card img{
  width:200px;
  height:200px;
  object-fit:cover;
  cursor:pointer;
  border:1px solid #eee;
}
</style>

<script src="https://unpkg.com/vue@3"></script>
<script>
const PAGE_UID = <?= json_encode($page_uid) ?>;
const BASE_URL = `https://storage.googleapis.com/tabiguide_uploads/upload/${PAGE_UID}/images/map/`;
const EXT      = 'jpg';          // 統一拡張子

const { createApp } = Vue;
createApp({
  data(){
    return {
      maps: {},          // key = index (1..5) → obj
      maxMaps: 3,
      cover:{ has:false, url:'', displayUrl:'' },
    };
  },
  mounted(){ this.loadExisting(); this.loadCover(); },
  methods:{
    noCache(u){ return u + '?t=' + Date.now(); },
    getDisplayUrl(i){
      const m = this.maps[i] || null;
      return m ? m.displayUrl : '/assets/images/no_image.png';
    },
    hasImage(i){ return !!this.maps[i]; },
    triggerFile(i){
      this.$refs['file'+i][0].click();
    },
    handleFile(i,e){
      const file = e.target.files[0];
      if(!file) return;
      const fd = new FormData();
      fd.append('page_uid', PAGE_UID);
      fd.append('index', i);
      fd.append('map', file);
      fetch('/dashboard/facility/map_upload.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(r=>{
          if(r.ok){
            const url = `${BASE_URL}${i}.${EXT}`;
            this.maps[i] = { key:`${i}.${EXT}`, url, displayUrl:this.noCache(url)};
          } else {
            alert(r.error);
          }
        });
    },
    loadExisting(){
      this.maps = {};
      for(let i=1;i<=this.maxMaps;i++){
        const base = `${BASE_URL}${i}.${EXT}`;
        const url  = this.noCache(base);
        const img  = new Image();
        img.onload = ()=>{
          this.maps[i] = {key:`${i}.${EXT}`, url:base, displayUrl:url};
        };
        img.src = url;
      }
    },
    del(i){
      if(!confirm('削除しますか?')) return;
      fetch('/dashboard/facility/map_delete.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({page_uid: PAGE_UID, map_key:`${i}.${EXT}`})
      })
      .then(r=>r.json())
      .then(r=>{
        if(r.ok){
          delete this.maps[i];
        }else{
          alert(r.error);
        }
      });
    },
    triggerCover(){
      this.$refs.coverFile.click();
    },
    handleCover(e){
      const file = e.target.files[0];
      if(!file) return;
      const fd=new FormData();
      fd.append('page_uid', PAGE_UID);
      fd.append('map', file);
      fetch('/dashboard/facility/facility_upload.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(r=>{
          if(r.ok){
            const url = `${BASE_URL}facility.jpg`;
            this.cover={has:true,url,displayUrl:this.noCache(url)};
          }else{ alert(r.error); }
        });
    },
    loadCover(){
      const base=`${BASE_URL}facility.jpg`;
      const url=this.noCache(base);
      const img=new Image();
      img.onload=()=>{this.cover={has:true,url:base,displayUrl:url};};
      img.onerror=()=>{this.cover={has:false,url:'',displayUrl:''};};
      img.src=url;
    },
    delCover(){
      if(!confirm('削除しますか?')) return;
      fetch('/dashboard/facility/facility_delete.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({page_uid:PAGE_UID})
      })
      .then(r=>r.json())
      .then(r=>{
         if(r.ok){this.cover={has:false,url:'',displayUrl:''};}
         else{alert(r.error);}
      });
    },
  }
}).mount('#mapApp');
</script>

</div>

</div>
</body>
</html>
