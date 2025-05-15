<?php //テスト
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
$pdo = require dirname(__DIR__) . '/../core/db.php';

$pageUid = $_GET['page_uid'] ?? '';
$stmt = $pdo->prepare(
  'SELECT id,question,answer,tags,pinned,hits,state,map_json
     FROM question
    WHERE page_uid=:uid AND type="public"
    ORDER BY pinned DESC,hits DESC,id DESC'
);
$stmt->execute([':uid' => $pageUid]);
$faqList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ゲストからの質問・学習情報</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link rel="stylesheet" href="/assets/css/admin_layout.css">
<link rel="stylesheet" href="/assets/css/admin_design.css">
<link rel="stylesheet" href="/assets/css/faq.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</style>
<style>
/* === map modal improved === */
.map-modal{
  max-width:800px;
  padding:12px 16px;
}
.map-preview{
  position:relative;
  border:1px solid #ccc;
  margin:12px auto;
  width:100%;
  max-width:740px;
  overflow:hidden;
}

.pin{
  position:absolute;
  width:14px;
  height:14px;
  border-radius:50%;
  background:#ff3b30;
  cursor:pointer;
  transition:background 0.2s;
  pointer-events:auto;
  transform:translate(-50%,-50%) scale(var(--pin-scale,1));
  transform-origin:center;
}
.pin:hover{
  background:#ff795d;
}
.pos-btn{
  margin-top:6px;
  padding:4px 10px;
  font-size:0.8rem;
  display:inline-flex;
  align-items:center;
  gap:4px;
  cursor:pointer;
}
.btn-row{
  display:flex;
  justify-content:flex-end;
  gap:12px;
  margin-top:12px;
}
.zoom-wrap{
  position:relative;
  width:100%;
  height:auto;
  touch-action:none;         /* allow pinch-zoom in mobile */
}
.zoom-wrap img{
  width:100%;
  height:auto;
  display:block;
}
.icon-map{
  font-size:18px;
  color:#2196f3;
  vertical-align:middle;
  margin-right:4px;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/panzoom@9.4.0/dist/panzoom.min.js"></script>
</head>
<body>
<?php include dirname(__DIR__) . '/components/dashboard_header.php'; ?>
<div class="dashboard-container">
<?php include dirname(__DIR__) . '/components/side_navi.php'; ?>
<div id="app" class="container">
<main>
<h1>ゲストからの質問・学習情報</h1>

<div class="tab-nav">
  <button v-for="t in tabs" :key="t.key"
          @click="active=t.key"
          :class="{active:active===t.key}">{{t.label}}</button>
</div>

<button @click="openNew()" type="button">＋新しい学習情報を追加</button>

<!-- 新規 FAQ モーダル -->
<div v-if="newForm.open" class="modal-mask" @click.self="closeNew">
  <div class="modal-body">
    <h3>新しい学習情報を追加</h3>
    <textarea rows="4" v-model="newForm.question" placeholder="質問を入力..."></textarea>
    <textarea rows="4" v-model="newForm.answer"   placeholder="回答（空でも可）"></textarea>
    <button class="pos-btn" @click.stop="openMap('new')">
      <span class="material-icons">place</span> 位置情報
    </button>
    <div class="btn-row">
      <button @click="closeNew">キャンセル</button>
      <button @click="createNew">保存</button>
    </div>
  </div>
</div>
<br>
<br>


<div v-for="f in filtered" :key="f.id" class="faq-item-wrap" :class="{ edit: f.editing} ">

  <div class="faq-item"  @click.self="!f.editing && startEdit(f)" >

    <!-- pin -->
    <button class="icon-btn icon-star" :class="{pinned:f.pinned==1}"
            @click.stop="togglePin(f)">
      <span class="material-icons">{{f.pinned==1?'star':'star_outline'}}</span>
    </button>

    <!-- delete / archive -->
    <button v-if="!f.editing" class="icon-btn icon-del" @click.stop="remove(f.id)">
      <span class="material-icons">delete</span><span class="icon_label">削除</span>
    </button>

    <button v-if="!f.editing" class="icon-btn icon-arch" @click.stop="archive(f)">
      <span class="material-icons">{{f.state==='archive'?'unarchive':'archive'}}</span>
      <span class="icon_label">{{f.state==='archive' ? '表示にする' : '非表示にする'  }}</span>
    </button>

    <!-- save -->
    <button v-if="f.editing" class="icon-btn icon-save" @click.stop="save(f)">
      <span class="material-icons">check</span><span class="icon_label save_icon_label">保存</span>
    </button>

    <!-- question -->
    <div v-if="f.editing" class="editing_text" @click.self=" f.editing = false ">質問を編集中</div>

    <div v-if="!f.editing" @click.self="!f.editing && startEdit(f)" class="q">
      {{f.question}}<span v-if="f.map_json" class="icon-map material-icons">place</span>

    </div>
    <textarea v-else rows="2" class="question-box"
              v-model="f.editQuestion" placeholder="シャンプーのストックはどこ？"></textarea>

    <!-- answer -->
    <div v-if="!f.editing" @click.self="!f.editing && startEdit(f)" v-html=" f.answer ? '' + f.answer : '<em>未回答</em>'"></div>
    <textarea v-else rows="5" class="answer-box"
              v-model="f.editAnswer" placeholder="脱衣所の引き出しにあります。"></textarea>
    <!-- map pin button -->
    <button v-if="f.editing" class="pos-btn" @click.stop="openMap(f)">
      <span class="material-icons">place</span> 位置情報
    </button>


    <!-- tags -->
    <!-- <small v-if="!f.editing">#{{f.tags||'タグなし'}}</small>
    <input  v-else class="tag-box" v-model="f.editTags" placeholder="カンマ区切りタグ"> -->
  </div>
</div>


<!-- 位置情報モーダル -->
<div v-if="mapForm.open" class="modal-mask" @click.self="closeMap">
  <div class="modal-body map-modal">
    <h3>マップにピンを追加</h3>

    <label>マップ画像:
      <select v-model="mapForm.map">
        <option value="1">マップ1</option>
        <option value="2">マップ2</option>
        <option value="3">マップ2</option>
      </select>
    </label>

    <div class="map-preview">
      <div class="zoom-wrap" ref="zoomArea" @pointerdown="pinDown" @pointerup="pinUp">
        <img :src="mapUrl(mapForm.map)" ref="mapImg">
        <div v-for="(p,idx) in mapForm.pins" :key="idx"
             class="pin"
             :data-idx="idx+1"
             :style="{left:p.x_pct+'%',top:p.y_pct+'%'}"
             @click.stop="removePin(idx)"
             @pointerdown.stop
             @pointerup.stop></div>
      </div>
    </div>

    <div class="btn-row">
      <button @click="closeMap">キャンセル</button>
      <button @click="saveMap">保存</button>
    </div>
  </div>
</div>
</main>
</div>
</div>

<script type="module">
import {createApp} from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';

const PAGE_UID = '<?= $pageUid ?>';  // サーバー側で埋め込んだページUID

const raw = <?php echo json_encode($faqList, JSON_UNESCAPED_UNICODE);?>;


createApp({
  data(){
    raw.forEach(r=>{
      // parse map_json (string -> object) if exists
      if(r.map_json){
        try{ r.map_json = JSON.parse(r.map_json); }catch(e){ r.map_json = null; }
      }
      Object.assign(r,{
        editQuestion:r.question,
        editAnswer:r.answer,
        editTags:r.tags,
        editing:false
      });
    });
    return{
      list:raw,
      active:'new',
      tabs:[
        {key:'new',label:'未回答'},
        {key:'answered',label:'回答済み'},
        {key:'pinned',label:'重要'},
        {key:'arch',label:'アーカイブ'},
        {key:'all',label:'すべて'}
      ],
      newForm:{open:false,question:'',answer:'',tags:'', map_json:null},
      mapForm:{ open:false, target:null, map:'1', pins:[], isNew:false },
      pz:null,
      pinStart:{x:0,y:0,time:0},
      currentScale:1,
      coverScale:1, // Minimum scale to fill preview area
    }
  },
  computed:{
    filtered () {
      let a = this.list;

      if (this.active === 'pinned') {
        a = a.filter(v => v.pinned == 1 && v.state !== 'archive');

      } else if (this.active === 'new') {
        a = a.filter(v => !v.answer && v.state !== 'archive');

      } else if (this.active === 'answered') {
        a = a.filter(v => v.answer && v.state !== 'archive');

      } else if (this.active === 'arch') {
        a = a.filter(v => v.state === 'archive');
      }

      return _.orderBy(
        a,
        ['pinned', 'updated_at', 'hits', 'id'],
        ['desc',   'desc',       'desc', 'desc']
      );
    }
  },

  methods:{
    /* 編集開始 */
    startEdit(f){
      console.log('[startEdit]', f.id);
      this.list.forEach(v=>v.editing=false);
      Object.assign(f,{
        editQuestion:f.question,
        editAnswer:f.answer,
        editTags:f.tags,
        editing:true
      });
    },
    endEdit(f) {
      console.log('[end]', f.id);
      this.list.forEach(v=>v.editing=false);
      Object.assign(f,{
        editQuestion:f.question,
        editAnswer:f.answer,
        editTags:f.tags,
        editing:false
      });    
    },
    /* ピン切替 */
    async togglePin(f){
      console.log('[togglePin] id=',f.id,'→',f.pinned?0:1);
      await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mode:'toggle_pin',id:f.id,page_uid:PAGE_UID})
      });
      f.pinned = f.pinned?0:1;
    },

    /* 削除 */
    async remove(id){
      console.log('[remove]', id);
      if(!confirm('削除しますか？')) return;
      await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mode:'delete',id,page_uid:PAGE_UID})
      });
      this.list = this.list.filter(v=>v.id!==id);
    },

    /* アーカイブ */
    async archive(f){
      const mode = f.state==='archive'?'unarchive':'archive';
      console.log('[archive]', f.id, mode);
      await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mode:mode,id:f.id,page_uid:PAGE_UID})
      });
      f.state = mode==='archive' ? 'archive' : (f.answer?'reply':'draft');
    },

    /* 更新保存 */
    async save(f){
      console.log('[save] req', f.id);
      
      const body = {
        mode:'update',
        page_uid: PAGE_UID,
        id:f.id,
        question:f.editQuestion,
        answer:f.editAnswer,
        tags:f.editTags,
        map_json: f.map_json || null
      };
      const res  = await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(body)
      });

      const json = await res.json();
      console.log('[save] res', json);

      if(!json.ok){                // 失敗時は抜ける
        alert('保存失敗: '+(json.error||'unknown'));
        return;
      }

      Object.assign(f,{
        question:f.editQuestion,
        answer:f.editAnswer,
        tags:f.editTags,
        editing:false
      });
      if(f.answer && f.state!=='archive') f.state='reply';
    },

    /* 新規モーダル */
    openNew(){
      console.log('[openNew]');
      this.newForm={open:true,question:'',answer:'',tags:'', map_json:null};
    },
    closeNew(){
      console.log('[closeNew]');
      this.newForm.open=false;
    },

    /* 新規作成 */
    async createNew(){
      console.log('[createNew]');
      if(this.newForm.question.trim()===''){
        alert('質問を入力してください');
        return;
      }
      const res = await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          mode:'create',
          page_uid:'<?= $pageUid ?>',
          question:this.newForm.question,
          answer:this.newForm.answer,
          tags:this.newForm.tags,
          map_json:this.newForm.map_json
        })
      }).then(r=>r.json());

      console.log('[createNew] response', res);

      if(res.ok){
        this.list.unshift({
          id:res.id,
          question:this.newForm.question,
          answer:this.newForm.answer,
          tags:this.newForm.tags,
          map_json:this.newForm.map_json,
          pinned:0,hits:0,state:this.newForm.answer?'reply':'draft',
          editQuestion:'',editAnswer:'',editTags:'',editing:false
        });
        this.closeNew();
        this.active = this.newForm.answer ? 'answered' : 'new';
      }
    },

    /* --- map pin helpers --- */
    mapUrl(num){
      const base = `https://storage.googleapis.com/tabiguide_uploads/upload/${PAGE_UID}/images/map/${num}.jpg`;
      return base + '?t=' + Date.now();           // cache‑bust
    },

    openMap(target){
      const isNew = (target === 'new');
      const obj   = isNew ? this.newForm : target;

      this.mapForm = {
        open:true,
        target:obj,
        isNew:isNew,
        map:(obj.map_json && obj.map_json.map) || '1',
        pins:(obj.map_json && obj.map_json.pins)
             ? JSON.parse(JSON.stringify(obj.map_json.pins))
             : []
      };
      this.initPanzoom();
    },
    closeMap(){
      if(this.pz){ this.pz.dispose(); this.pz=null; }
      this.mapForm.open=false;
    },
    addPin(e){
      const rect = this.$refs.mapImg.getBoundingClientRect();
      const x = ((e.clientX - rect.left)/rect.width*100).toFixed(1);
      const y = ((e.clientY - rect.top )/rect.height*100).toFixed(1);
      this.mapForm.pins.push({x_pct:Number(x), y_pct:Number(y), label:''});
    },
    saveMap(){
      const data = { map:this.mapForm.map, pins:this.mapForm.pins };
      if(this.mapForm.isNew){
        this.newForm.map_json = data;
      }else if(this.mapForm.target){
        this.mapForm.target.map_json = data;
      }
      this.closeMap();
    },
    removePin(i){
      this.mapForm.pins.splice(i,1);
    },
    applyPinScale(s){
      this.$refs.zoomArea.style.setProperty('--pin-scale', (1/s).toString());
    },
    initPanzoom(){
      this.$nextTick(()=> {
        if(this.pz){ this.pz.dispose(); this.pz=null; }
        const el = this.$refs.zoomArea;
        if(!el) return;
        // wait for image to finish loading so naturalWidth / Height are valid
        if(!this.$refs.mapImg.complete){
          this.$refs.mapImg.addEventListener('load', () => this.initPanzoom(), {once:true});
          return;
        }
        const pzFn = window.panzoom || window.Panzoom;
        if (typeof pzFn !== 'function') {
          console.error('Panzoom library not loaded');
          return;
        }
        // --- helper to get current scale regardless of panzoom version ---
        const getScale = () => {
          if (this.pz && this.pz.getScale) return this.pz.getScale();
          const t = this.pz && this.pz.getTransform ? this.pz.getTransform() : {scale:1};
          return t.scale || 1;
        };
        // center the image at current transform, keeping it within the wrapper
        function centerImage(){
          if (!this.pz) return;
          if (typeof this.pz.pan === 'function') {
            this.pz.pan(0, 0);
          } else if (typeof this.pz.moveTo === 'function') {
            this.pz.moveTo(0, 0);
          } else if (this.pz.setTransform && this.pz.getTransform) {
            const t = this.pz.getTransform();
            this.pz.setTransform({...t, x:0, y:0});
          }
        }
        // --- robust compute coverScale (minimum zoom to fill wrapper, avoid blank) ---
        const computeCoverScale = () => {
          const img = this.$refs.mapImg;
          if(!img || !img.naturalWidth || !img.naturalHeight){
            return 1;                      // fallback until image loads
          }
          const wrap = el;                 // zoomArea container
          const cw = wrap.clientWidth  || 1;
          const ch = wrap.clientHeight || 1;
          const iw = img.naturalWidth;
          const ih = img.naturalHeight;
          const ratio = Math.max(cw/iw, ch/ih) * 1.01;
          return ratio < 1 ? 1 : ratio;           // never smaller than 1
        };
        this.coverScale = computeCoverScale();
        this.pz = pzFn(el, {
          maxScale: 5,
          minScale: this.coverScale,
          contain: 'cover',   // image must always cover the wrapper (no blank)
          beforePan: () => getScale() > this.coverScale + 0.001
        });
        // Set initial zoom so image fills wrapper
        this.pz.zoomAbs(el.clientWidth/2, el.clientHeight/2, this.coverScale);
        // --- hoisted function: keep pins constant size ---
        function updatePinScale(scaleVal){
          this.currentScale = scaleVal || getScale() || 1;
          if (this.currentScale < this.coverScale){
            this.pz.zoomAbs(0,0,this.coverScale);
            this.currentScale = this.coverScale;
          }
          this.applyPinScale(this.currentScale);
        }
        centerImage.call(this);
        updatePinScale.call(this, this.coverScale);
        // initial call
        updatePinScale.call(this, getScale());
        // listen via panzoom's built‑in event hook
        this.pz.on('zoom', (e) => {
           updatePinScale.call(this, getScale());
        });
        // pan event: re-apply constraints to ensure image always covers
        this.pz.on('pan', () => {
          if (typeof this.pz.applyConstraints === 'function') {
            this.pz.applyConstraints(); // keep inside after drag
          }
        });

        // custom wheel handler: zoom in at pointer, zoom out toward center
        const wheelHandler = ev => {
          ev.preventDefault();
          const scale = getScale();
          const zoomIn  = ev.deltaY < 0;
          let next = zoomIn ? scale * 1.1 : scale / 1.1;
          if(!isFinite(next)) next = this.coverScale;
          next = Math.max(this.coverScale, Math.min(5, next));

          let cx, cy;
          if(zoomIn){
            // use pointer position (relative to element) for zoom in
            cx = ev.offsetX;
            cy = ev.offsetY;
          }else{
            // use element center for zoom out to avoid diagonal drift
            cx = el.clientWidth  / 2;
            cy = el.clientHeight / 2;
          }
          this.pz.zoomAbs(cx, cy, next);

          // if we reached minimum scale, reset pan to origin so image is centered
          if(next === this.coverScale){
            // centre via pan(0,0) or equivalent
            if (typeof this.pz.pan === 'function')      this.pz.pan(0,0);
            else if (typeof this.pz.moveTo === 'function') this.pz.moveTo(0,0);
            else if (this.pz.setTransform && this.pz.getTransform){
              const t=this.pz.getTransform(); this.pz.setTransform({...t,x:0,y:0});
            }
          }

          // extra clamp (old + new versions)
          if (typeof this.pz.applyConstraints === 'function') {
            this.pz.applyConstraints();
          }
        };
        el.parentElement.addEventListener('wheel', wheelHandler, {passive:false});
      });
    },
    pinDown(e){
      this.pinStart = {x:e.clientX, y:e.clientY, time: Date.now()};
    },
    pinUp(e){
      if(e.target.classList.contains('pin')) return; // ignore clicks on pins
      const dx = e.clientX - this.pinStart.x;
      const dy = e.clientY - this.pinStart.y;
      const dist = Math.sqrt(dx*dx + dy*dy);
      const dt = Date.now() - this.pinStart.time;
      // treat as click if small movement (<5px) and quick (<300ms)
      if(dist < 5 && dt < 300){
        this.addPin(e);
      }
    },
  },

  watch:{
    'mapForm.map'(){
      // Wait DOM update then recreate panzoom
      this.$nextTick(()=> this.initPanzoom());
    }
  }

}).mount('#app');
</script>
</body>
</html>
