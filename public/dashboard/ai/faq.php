<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
$pdo = require dirname(__DIR__) . '/../core/db.php';

$pageUid = $_GET['page_uid'] ?? '';
$stmt = $pdo->prepare(
  'SELECT id,question,answer,tags,pinned,hits,state
     FROM question
    WHERE page_uid=:uid
    ORDER BY pinned DESC,hits DESC,id DESC'
);
$stmt->execute([':uid' => $pageUid]);
$faqList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>FAQ 管理</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link rel="stylesheet" href="/assets/css/admin_layout.css">
<link rel="stylesheet" href="/assets/css/admin_design.css">
<link rel="stylesheet" href="/assets/css/faq.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
</style>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js" defer></script>
</head>
<body>
<?php include dirname(__DIR__) . '/components/dashboard_header.php'; ?>
<div class="dashboard-container">
<?php include dirname(__DIR__) . '/components/side_navi.php'; ?>
<div id="app" class="container">
<main>
<h1>よくある質問</h1>

<div class="tab-nav">
  <button v-for="t in tabs" :key="t.key"
          @click="active=t.key"
          :class="{active:active===t.key}">{{t.label}}</button>
</div>

<button @click="openNew()" type="button">＋新規 FAQ</button>

<!-- 新規 FAQ モーダル -->
<div v-if="newForm.open" class="modal-mask" @click.self="closeNew">
  <div class="modal-body">
    <h3>新規 FAQ</h3>
    <textarea rows="4" v-model="newForm.question" placeholder="質問を入力..."></textarea>
    <textarea rows="4" v-model="newForm.answer"   placeholder="回答（空でも可）"></textarea>
    <input    type="text"       v-model="newForm.tags"     placeholder="タグをカンマ区切り">
    <div class="btn-row">
      <button @click="closeNew">キャンセル</button>
      <button @click="createNew">保存</button>
    </div>
  </div>
</div>
<br>
<br>


<div v-for="f in filtered" :key="f.id" class="faq-item-wrap" :class="{ edit: f.editing} "
     @click="!f.editing && startEdit(f)">

  <div class="faq-item" >

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
    <div v-if="f.editing" class="editing_text">質問を編集中</div>

    <div v-if="!f.editing" class="q">Q: {{f.question}}</div>
    <textarea v-else rows="2" class="question-box"
              v-model="f.editQuestion" placeholder="質問を入力..."></textarea>

    <!-- answer -->
    <div v-if="!f.editing" v-html=" f.answer ? 'A: ' + f.answer : '<em>未回答</em>'"></div>
    <textarea v-else rows="5" class="answer-box"
              v-model="f.editAnswer" placeholder="回答を入力..."></textarea>

    <!-- tags -->
    <small v-if="!f.editing">#{{f.tags||'タグなし'}}</small>
    <input  v-else class="tag-box" v-model="f.editTags" placeholder="カンマ区切りタグ">
  </div>
</div>
</main>
</div>
</div>

<script type="module">
import {createApp} from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';

const raw = <?php echo json_encode($faqList, JSON_UNESCAPED_UNICODE);?>;

createApp({
  data(){
    raw.forEach(r=>Object.assign(r,{
      editQuestion:r.question,
      editAnswer:r.answer,
      editTags:r.tags,
      editing:false
    }));
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
      newForm:{open:false,question:'',answer:'',tags:''}
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

    /* ピン切替 */
    async togglePin(f){
      console.log('[togglePin] id=',f.id,'→',f.pinned?0:1);
      await fetch('/api/faq/save.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({mode:'toggle_pin',id:f.id})
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
        body:JSON.stringify({mode:'delete',id})
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
        body:JSON.stringify({mode:mode,id:f.id})
      });
      f.state = mode==='archive' ? 'archive' : (f.answer?'reply':'draft');
    },

    /* 更新保存 */
    async save(f){
      console.log('[save] req', f.id);

      const body = {
        mode:'update',
        id:f.id,
        question:f.editQuestion,
        answer:f.editAnswer,
        tags:f.editTags
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
      this.newForm={open:true,question:'',answer:'',tags:''};
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
          tags:this.newForm.tags
        })
      }).then(r=>r.json());

      console.log('[createNew] response', res);

      if(res.ok){
        this.list.unshift({
          id:res.id,
          question:this.newForm.question,
          answer:this.newForm.answer,
          tags:this.newForm.tags,
          pinned:0,hits:0,state:this.newForm.answer?'reply':'draft',
          editQuestion:'',editAnswer:'',editTags:'',editing:false
        });
        this.closeNew();
        this.active = this.newForm.answer ? 'answered' : 'new';
      }
    }
  }


}).mount('#app');
</script>
</body>
</html>
