<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? null;

if (!$page_uid) {
    echo "施設が選択されていません。";
    exit;
}

// デザイン設定を取得
$stmt = $pdo->prepare("SELECT design_json FROM design WHERE page_uid = :page_uid");
$stmt->bindParam(':page_uid', $page_uid);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// デフォルト値
$design = [
    'primary_color' => '#1976D2',
    'secondary_color' => '#2196F3',
    'accent_color' => '#FF5722',
    'header_text_color' => '#FFFFFF',
    'tab_active_color' => '#FFFFFF',
    'tab_inactive_color' => 'rgba(255,255,255,0.7)',
    'bot_message_color' => '#E3F2FD',
    'bot_text_color' => '#333333',
    'user_message_color' => '#333333',
    'user_text_color' => '#333333',
    'message_text_color' => '#333333',
    'input_background_color' => '#FFFFFF',
    'bg_filter_color' => '#000000',
    'bg_filter_opacity' => 0.2,
    'bg_filter_blur' => 4,
    'font_family' => 'Noto Sans JP',
    'page_uid' => $page_uid,
    'background_url' => null,
    'icon_url' => null,
    'send_button_color' => '#1976D2',
    'send_button_bg_color' => '#FFFFFF',
    'header_logo_url' => null
];

// 保存されているデザイン設定があれば上書き
if ($result && $result['design_json']) {
    $saved_design = json_decode($result['design_json'], true);
    if ($saved_design) {
        $design = array_merge($design, $saved_design);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>デザイン設定</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
    <link rel="stylesheet" href="/assets/css/admin_layout.css">
    <link rel="stylesheet" href="/assets/css/admin_design.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=M+PLUS+1p:wght@400;500;700&family=Zen+Kaku+Gothic+New:wght@400;500;700&family=BIZ+UDPGothic:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="design.css">
    <style>
    .chat-header {
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 16px;
    }

    .header-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }

    .header-logo img {
      max-height: 95%;
      max-width: 80%;
      object-fit: contain;
    }
    </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

  <div class="dashboard-container">
    <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>
    <div id="app">
      <main>
        <h1>デザイン設定</h1>

        <div class="layout-container">
          <!-- プレビューエリア -->
          <div class="preview-area">
            <div class="preview-phones">
              <!-- チャットプレビュー -->
              <div class="phone-wrapper">
                <div class="phone-frame">
                  <div class="phone-header">
                    <div class="phone-notch"></div>
                  </div>
                  <div class="phone-content">
                    <!-- チャット画面 -->
                    <div class="guest-chat"
                      :style="{
                        backgroundImage: preview.background_url 
                          ? `url('${preview.background_url}')` 
                          : design.background_url 
                            ? `url('/upload/${design.page_uid}/images/background.jpg')`
                            : 'none',
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                        backgroundRepeat: 'no-repeat'
                      }"
                    >
                      <div class="bg-filter"
                        :style="{
                          background: hexToRgba(design.bg_filter_color, design.bg_filter_opacity),
                          backdropFilter: `blur(${design.bg_filter_blur}px)`,
                          zIndex: 0
                        }"
                      ></div>
                      <!-- ヘッダー -->
                      <div class="chat-header" :style="{ backgroundColor: design.primary_color }">
                        <div v-if="preview.header_logo_url || design.header_logo_url" class="header-logo">
                          <img :src="preview.header_logo_url || `/upload/${design.page_uid}/images/header_logo.jpg`" alt="施設ロゴ">
                        </div>
                        <h1 v-else :style="{ color: design.header_text_color }">海辺の家</h1>
                      </div>

                      <div class="chat-messages">
                        <div class="chat-message bot">
                          <div class="bot-avatar"
                            :style="{
                              backgroundImage: preview.icon_url 
                                ? `url('${preview.icon_url}')` 
                                : design.icon_url 
                                  ? `url('/upload/${design.page_uid}/images/icon.jpg')`
                                  : 'none',
                              backgroundColor: '#FFFFFF',
                              backgroundSize: 'cover',
                              backgroundPosition: 'center',
                              backgroundRepeat: 'no-repeat'
                            }"
                          ></div>
                          <div class="message-content" :style="{ color: design.bot_text_color ,  backgroundColor: design.bot_message_color }">
                            ようこそ〜近くのおすすめグルメや観光スポットもご案内できますので、遠慮なくご相談ください。
                          </div>
                        </div>
                        <div class="chat-message user"  >
                          <div class="message-content" :style="{ color: design.user_text_color  , backgroundColor: design.user_message_color }">
                            チェックインの時間を教えてください。
                          </div>
                        </div>
                      </div>

                      <div class="guest-input" :style="{ backgroundColor: design.input_background_color }">
                        <input type="text" placeholder="メッセージを入力">
                        <button :style="{ backgroundColor: design.send_button_bg_color }">
                          <span class="material-symbols-outlined send" :style="{ color: design.send_button_color }" >send</span>
                        </button>
                      </div>

                      <!-- タブバー -->
                      <div class="guest-tabs" :style="{ backgroundColor: design.secondary_color }">
                        <button class="tab-button active" :style="{ color: design.tab_active_color }">
                          <span class="material-symbols-outlined">chat</span>
                          <span>チャット</span>
                        </button>
                        <button class="tab-button" :style="{ color: design.tab_inactive_color }">
                          <span class="material-symbols-outlined">map</span>
                          <span>周辺マップ</span>
                        </button>
                        <button class="tab-button" :style="{ color: design.tab_inactive_color }">
                          <span class="material-symbols-outlined">schedule</span>
                          <span>基本情報</span>
                        </button>
                        <button class="tab-button" :style="{ color: design.tab_inactive_color }">
                          <span class="material-symbols-outlined">translate</span>
                          <span>翻訳</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="phone-label">チャット画面</div>
              </div>
            </div>
          </div>

          <!-- 設定パネル -->
          <div class="settings-panel">
            <div class="template-selector">
              <h3>テンプレート</h3>
              <div class="template-grid">
                <button v-for="(template, key) in templates" 
                        :key="key"
                        class="template-button"
                        @click="applyTemplate(template)"
                        :class="{ active: isCurrentTemplate(template) }">
                  {{ template.name }}
                </button>
              </div>
            </div>

            <div class="color-palette">
              <h3>カラー設定</h3>
              <div class="palette-row">
                <div class="color-group">
                  <label>メインカラー</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.primary_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>サブカラー</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.secondary_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>ヘッダー文字色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.header_text_color">
                  </div>
                </div>
              </div>
              <div class="palette-row">
                <div class="color-group">
                  <label>ボットメッセージ背景</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.bot_message_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>ボットメッセージ文字色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.bot_text_color">
                  </div>
                </div>
              </div>
              <div class="palette-row">
                <div class="color-group">
                  <label>ユーザーメッセージ文字色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.user_message_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>ユーザーメッセージ文字色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.user_text_color">
                  </div>
                </div>
              </div>
              <div class="palette-row">
                <div class="color-group">
                  <label>入力欄背景色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.input_background_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>送信ボタン色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.send_button_color">
                  </div>
                </div>
                <div class="color-group">
                  <label>送信ボタン背景色</label>
                  <div class="color-picker">
                    <input type="color" v-model="design.send_button_bg_color">
                  </div>
                </div>
              </div>
            </div>

            <div class="image-preview">
              <h3>画像設定</h3>
              <div class="image-grid">
                <div class="image-item">
                  <div class="image-upload-area" 
                       :class="{ 'has-image': preview.icon_url || design.icon_url }"
                       @click="triggerFileInput('icon')"
                       :style="{ backgroundImage: preview.icon_url ? `url('${preview.icon_url}')` : design.icon_url ? `url('/upload/${design.page_uid}/images/icon.jpg')` : 'none' }">
                    <input type="file" 
                           ref="iconInput" 
                           accept="image/*"
                           @change="handleImageUpload('icon', $event)" 
                           style="display: none">
                    <span v-if="!preview.icon_url && !design.icon_url" class="upload-placeholder">
                      <span class="material-symbols-outlined">add_photo_alternate</span>
                      <span>アイコンを選択</span>
                    </span>
                    <button v-if="preview.icon_url || design.icon_url" 
                            class="delete-image-button"
                            @click.stop="deleteImage('icon')">
                      <span class="material-symbols-outlined">delete</span>
                    </button>
                  </div>
                  <span>施設アイコン</span>
                </div>

                <div class="image-item">
                  <div class="image-upload-area"
                       :class="{ 'has-image': preview.background_url || design.background_url }"
                       @click="triggerFileInput('background')"
                       :style="{ backgroundImage: preview.background_url ? `url('${preview.background_url}')` : design.background_url ? `url('/upload/${design.page_uid}/images/background.jpg')` : 'none' }">
                    <input type="file" 
                           ref="backgroundInput" 
                           accept="image/*"
                           @change="handleImageUpload('background', $event)" 
                           style="display: none">
                    <span v-if="!preview.background_url && !design.background_url" class="upload-placeholder">
                      <span class="material-symbols-outlined">add_photo_alternate</span>
                      <span>背景画像を選択</span>
                    </span>
                    <button v-if="preview.background_url || design.background_url" 
                            class="delete-image-button"
                            @click.stop="deleteImage('background')">
                      <span class="material-symbols-outlined">delete</span>
                    </button>
                  </div>
                  <span>チャット背景</span>
                </div>

                <div class="image-item">
                  <div class="image-upload-area"
                       :class="{ 'has-image': preview.header_logo_url || design.header_logo_url }"
                       @click="triggerFileInput('header_logo')"
                       :style="{ backgroundImage: preview.header_logo_url ? `url('${preview.header_logo_url}')` : design.header_logo_url ? `url('/upload/${design.page_uid}/images/header_logo.jpg')` : 'none' }">
                    <input type="file" 
                           ref="header_logoInput" 
                           accept="image/*"
                           @change="handleImageUpload('header_logo', $event)" 
                           style="display: none">
                    <span v-if="!preview.header_logo_url && !design.header_logo_url" class="upload-placeholder">
                      <span class="material-symbols-outlined">add_photo_alternate</span>
                      <span>ヘッダーロゴを選択</span>
                    </span>
                    <button v-if="preview.header_logo_url || design.header_logo_url" 
                            class="delete-image-button"
                            @click.stop="deleteImage('header_logo')">
                      <span class="material-symbols-outlined">delete</span>
                    </button>
                  </div>
                  <span>ヘッダーロゴ</span>
                </div>
              </div>
            </div>

            <!-- フィルター設定UIを追加 -->
            <div class="filter-settings">
              <div class="filter-item">
                <label>背景フィルター色 (rgba)</label>
                <div class="filter-controls">
                  <input type="color" v-model="design.bg_filter_color">
                  <input type="range" min="0" max="1" step="0.01" v-model.number="design.bg_filter_opacity">
                  <span class="filter-value">{{ design.bg_filter_color }} / {{ design.bg_filter_opacity }}</span>
                </div>
              </div>
              <div class="filter-item">
                <label>ぼかし (px)</label>
                <div class="filter-controls">
                  <input type="range" min="0" max="20" step="1" v-model.number="design.bg_filter_blur">
                  <span class="filter-value">{{ design.bg_filter_blur }}px</span>
                </div>
              </div>
            </div>

            <div class="font-selector">
              <h3>フォント設定</h3>
              <select v-model="design.font_family" class="font-select">
                <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">
                  {{ font.name }}
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button type="submit" class="save-button" @click="saveDesign">設定を保存</button>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>


  <script>
    const app = Vue.createApp({
        data() {
            return {
                design: <?php echo json_encode($design); ?>,
                preview: {
                    icon_url: null,
                    background_url: null
                },
                templates: null,
                availableFonts: [
                    { name: 'Noto Sans JP', value: 'Noto Sans JP' },
                    { name: 'M PLUS 1p', value: 'M PLUS 1p' },
                    { name: 'Zen Kaku Gothic New', value: 'Zen Kaku Gothic New' },
                    { name: 'BIZ UDPGothic', value: 'BIZ UDPGothic' }
                ]
            };
        },
        computed: {
            effectiveIconUrl() {
                return this.preview.icon_url || this.design.icon_url;
            },
            effectiveBackgroundUrl() {
                return this.preview.background_url || this.design.background_url;
            }
        },
        methods: {
            triggerFileInput(type) {
                this.$refs[`${type}Input`].click();
            },
            async handleImageUpload(type, event) {
                const file = event.target.files[0];
                if (!file) {
                    console.log('ファイルが選択されていません');
                    return;
                }

                // ファイルサイズチェック（5MB以下）
                if (file.size > 5 * 1024 * 1024) {
                    alert('ファイルサイズは5MB以下にしてください');
                    return;
                }

                // 画像タイプチェック
                if (!file.type.startsWith('image/')) {
                    alert('画像ファイルを選択してください');
                    return;
                }

                try {
                    // プレビュー用のURL生成
                    const previewUrl = URL.createObjectURL(file);
                    this.preview[`${type}_url`] = previewUrl;

                    // アップロード処理
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('type', type);
                    formData.append('page_uid', this.design.page_uid);

                    console.log('Uploading file:', {
                        name: file.name,
                        type: file.type,
                        size: file.size,
                        formType: type,
                        pageUid: this.design.page_uid
                    });

                    const response = await axios.post('/api/design/upload-image.php', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (progressEvent) => {
                            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            console.log('Upload progress:', percentCompleted + '%');
                        }
                    });
                    
                    console.log('Upload response:', response.data);
                    
                    if (response.data.success) {
                        this.design[`${type}_url`] = true;
                        console.log(`${type} 画像のアップロードに成功:`, response.data.url);
                    } else {
                        throw new Error(response.data.message || 'アップロードに失敗しました');
                    }
                } catch (error) {
                    console.error('アップロードエラーの詳細:', error.response?.data || error);
                    alert(`画像のアップロードに失敗しました: ${error.response?.data?.message || error.message}`);
                    this.preview[`${type}_url`] = null;
                    if (this.preview[`${type}_url`]) {
                        URL.revokeObjectURL(this.preview[`${type}_url`]);
                    }
                }
            },
            async saveDesign() {
                try {
                    const response = await axios.post('/api/design/save_design.php', {
                        design: this.design
                    });
                    
                    if (response.data.success) {
                        alert('デザイン設定を保存しました');
                    } else {
                        throw new Error(response.data.message || '保存に失敗しました');
                    }
                } catch (error) {
                    console.error('保存エラー:', error);
                    alert(error.response?.data?.message || '保存に失敗しました');
                }
            },
            hexToRgba(hex, alpha) {
                // hex: #RRGGBB
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                return `rgba(${r},${g},${b},${alpha})`;
            },
            async loadTemplates() {
                try {
                    const response = await fetch('/dashboard/design/templates.json');
                    this.templates = await response.json();
                } catch (error) {
                    console.error('テンプレートの読み込みに失敗しました:', error);
                }
            },
            applyTemplate(template) {
                // URLとフィルター設定を保持
                const currentUrls = {
                    icon_url: this.design.icon_url,
                    background_url: this.design.background_url
                };
                const currentFont = this.design.font_family;

                // テンプレートの設定を適用
                Object.assign(this.design, template);

                // 保持していた設定を戻す
                this.design.icon_url = currentUrls.icon_url;
                this.design.background_url = currentUrls.background_url;
                this.design.font_family = currentFont;
            },
            isCurrentTemplate(template) {
                return this.design.primary_color === template.primary_color &&
                       this.design.secondary_color === template.secondary_color;
            },
            async deleteImage(type) {
                try {
                    const response = await axios.post('/api/design/delete-image.php', {
                        type: type,
                        page_uid: this.design.page_uid
                    });
                    
                    if (response.data.success) {
                        // プレビューと設定をクリア
                        this.preview[`${type}_url`] = null;
                        this.design[`${type}_url`] = false;
                        if (this.preview[`${type}_url`]) {
                            URL.revokeObjectURL(this.preview[`${type}_url`]);
                        }
                        console.log(`${type} 画像の削除に成功`);
                    } else {
                        throw new Error(response.data.message || '削除に失敗しました');
                    }
                } catch (error) {
                    console.error('削除エラーの詳細:', error.response?.data || error);
                    alert(`画像の削除に失敗しました: ${error.response?.data?.message || error.message}`);
                }
            },
        },
        watch: {
            'preview.background_url': function(newVal) {
                console.log('background_url プレビュー変更:', newVal ? newVal.substring(0, 100) + '...' : 'null');
            },
            'preview.icon_url': function(newVal) {
                console.log('icon_url プレビュー変更:', newVal ? newVal.substring(0, 100) + '...' : 'null');
            },
            'design.background_url': function(newVal) {
                console.log('background_url 本番変更:', newVal);
            },
            'design.icon_url': function(newVal) {
                console.log('icon_url 本番変更:', newVal);
            }
        },
        mounted() {
            this.loadTemplates();
            console.log('初期状態:', {
                preview: this.preview,
                design: this.design
            });
        }
    }).mount('#app');
  </script>
</body>
</html>