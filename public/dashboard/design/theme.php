<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$user_uid = $_SESSION['user']['uid'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM design WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults = [
  'primary_color' => '#000000',
  'secondary_color' => '#6b6b6b',
  'background_color' => '#f5f5f5',
  'text_color' => '#333333',
  'chat_bubble_color_user' => '#e0e0e0',
  'chat_bubble_color_ai' => '#ffffff',
  'logo_url' => '',
  'background_image_url' => ''
];

$design = array_merge($defaults, $design ?: []);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>テーマ設定</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
  <style>
    .theme-editor {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      padding: 2rem;
      height: calc(100vh - 60px);
      overflow: hidden;
    }
    
    .preview-section {
      display: grid;
      grid-template-rows: 1fr 1fr;
      gap: 1rem;
      overflow: hidden;
    }
    
    .map-preview, .chat-preview {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      position: relative;
    }
    
    .map-container {
      height: 70%;
      background: #eee;
      position: relative;
    }
    
    .map-panel {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      padding: 1rem;
      border-radius: 12px 12px 0 0;
    }
    
    .chat-header {
      padding: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .chat-logo {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .chat-messages {
      padding: 1rem;
      overflow-y: auto;
      height: calc(100% - 64px);
    }
    
    .message {
      padding: 0.8rem 1rem;
      border-radius: 12px;
      margin-bottom: 0.8rem;
      max-width: 80%;
    }
    
    .message.ai {
      margin-right: auto;
    }
    
    .message.user {
      margin-left: auto;
    }
    
    .settings-section {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      overflow-y: auto;
    }
    
    .color-group {
      margin-bottom: 2rem;
    }
    
    .color-input {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    
    .color-input label {
      flex: 1;
    }
    
    .color-input input[type="color"] {
      width: 50px;
      height: 30px;
      padding: 0;
      border: none;
      border-radius: 4px;
    }
    
    .image-upload {
      margin-bottom: 1rem;
    }
    
    .image-preview {
      width: 100px;
      height: 100px;
      border-radius: 8px;
      object-fit: cover;
      margin-top: 0.5rem;
    }
    
    .btn-save {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 0.8rem 2rem;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 2rem;
    }
    
    .btn-save:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

  <div class="dashboard-container">
    <?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>
    
    <div class="container">
      <div id="app" class="theme-editor">
        <!-- プレビューセクション -->
        <div class="preview-section">
          <!-- マッププレビュー -->
          <div class="map-preview" :style="{ backgroundColor: settings.background_color }">
            <div class="map-container">
              <!-- マップのモックアップ -->
              <div class="mock-map"></div>
              <!-- マーカー -->
              <div class="marker" :style="{ backgroundColor: settings.primary_color }"></div>
            </div>
            <div class="map-panel" :style="panelStyle">
              <h3 :style="{ color: settings.text_color }">サンプル施設名</h3>
              <p :style="{ color: settings.text_color }">施設の説明文がここに表示されます。</p>
            </div>
          </div>

          <!-- チャットプレビュー -->
          <div class="chat-preview" :style="{ backgroundColor: settings.background_color }">
            <div class="chat-header" :style="{ backgroundColor: settings.primary_color }">
              <img v-if="settings.logo_url" :src="settings.logo_url" class="chat-logo" alt="Logo">
              <h3 style="color: white">チャット</h3>
            </div>
            <div class="chat-messages">
              <div class="message ai" :style="aiMessageStyle">
                いらっしゃいませ。ご質問がございましたら、お気軽にどうぞ。
              </div>
              <div class="message user" :style="userMessageStyle">
                周辺のおすすめスポットを教えてください。
              </div>
            </div>
          </div>
        </div>

        <!-- 設定セクション -->
        <div class="settings-section">
          <h2>テーマ設定</h2>
          
          <div class="color-group">
            <h3>カラー設定</h3>
            <div class="color-input">
              <label>メインカラー</label>
              <input type="color" v-model="settings.primary_color">
            </div>
            <div class="color-input">
              <label>アクセントカラー</label>
              <input type="color" v-model="settings.secondary_color">
            </div>
            <div class="color-input">
              <label>背景色</label>
              <input type="color" v-model="settings.background_color">
            </div>
            <div class="color-input">
              <label>テキスト色</label>
              <input type="color" v-model="settings.text_color">
            </div>
          </div>

          <div class="color-group">
            <h3>チャット設定</h3>
            <div class="color-input">
              <label>ユーザーメッセージ色</label>
              <input type="color" v-model="settings.chat_bubble_color_user">
            </div>
            <div class="color-input">
              <label>AIメッセージ色</label>
              <input type="color" v-model="settings.chat_bubble_color_ai">
            </div>
          </div>

          <div class="image-group">
            <h3>画像設定</h3>
            <div class="image-upload">
              <label>ロゴ画像</label>
              <input type="file" @change="handleLogoUpload" accept="image/*">
              <img v-if="settings.logo_url" :src="settings.logo_url" class="image-preview" alt="Logo preview">
            </div>
            <div class="image-upload">
              <label>背景画像</label>
              <input type="file" @change="handleBgUpload" accept="image/*">
              <img v-if="settings.background_image_url" :src="settings.background_image_url" class="image-preview" alt="Background preview">
            </div>
          </div>

          <button @click="saveSettings" class="btn-save">保存</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const { createApp, ref, computed } = Vue

    createApp({
      setup() {
        // 初期設定値
        const settings = ref(<?= json_encode($design) ?>)

        // スタイルの計算
        const panelStyle = computed(() => ({
          backgroundColor: 'white',
          borderTop: `2px solid ${settings.value.primary_color}`
        }))

        const userMessageStyle = computed(() => ({
          backgroundColor: settings.value.chat_bubble_color_user,
          color: settings.value.text_color
        }))

        const aiMessageStyle = computed(() => ({
          backgroundColor: settings.value.chat_bubble_color_ai,
          color: settings.value.text_color
        }))

        // ファイルアップロード処理
        const handleLogoUpload = async (event) => {
          const file = event.target.files[0]
          if (file) {
            const formData = new FormData()
            formData.append('logo', file)
            formData.append('page_uid', '<?= $page_uid ?>')
            try {
              const response = await fetch('/api/upload-logo.php', {
                method: 'POST',
                body: formData
              })
              const data = await response.json()
              if (data.success) {
                settings.value.logo_url = data.url
              }
            } catch (error) {
              console.error('Logo upload failed:', error)
            }
          }
        }

        const handleBgUpload = async (event) => {
          const file = event.target.files[0]
          if (file) {
            const formData = new FormData()
            formData.append('background', file)
            formData.append('page_uid', '<?= $page_uid ?>')
            try {
              const response = await fetch('/api/upload-background.php', {
                method: 'POST',
                body: formData
              })
              const data = await response.json()
              if (data.success) {
                settings.value.background_image_url = data.url
              }
            } catch (error) {
              console.error('Background upload failed:', error)
            }
          }
        }

        // 設定の保存
        const saveSettings = async () => {
          try {
            const response = await fetch('/api/save-design.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                page_uid: '<?= $page_uid ?>',
                ...settings.value
              })
            })
            if (response.ok) {
              alert('設定を保存しました')
            }
          } catch (error) {
            console.error('Save failed:', error)
            alert('保存に失敗しました')
          }
        }

        return {
          settings,
          panelStyle,
          userMessageStyle,
          aiMessageStyle,
          handleLogoUpload,
          handleBgUpload,
          saveSettings
        }
      }
    }).mount('#app')
  </script>
</body>
</html> 