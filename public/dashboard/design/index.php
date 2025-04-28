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
  'font_family' => 'system-ui, sans-serif',
  'button_radius' => 4,
  'chat_bubble_color_user' => '#e0e0e0',
  'chat_bubble_color_ai' => '#ffffff',
  'dark_mode' => 0
];

$design = array_merge($defaults, $design ?: []);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>デザイン設定</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js"></script>
  <style>
    .container {
      flex: 1;
      overflow-y: auto;
      padding: 32px;
      box-sizing: border-box;
    }
    
    main {
      max-width: 1200px;
      margin: 0 auto;
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }

    .setting-card {
      background: white;
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 1.5rem;
      transition: all 0.2s ease;
    }

    .setting-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .setting-card h2 {
      margin: 0 0 1rem;
      font-size: 1.25rem;
      color: #333;
    }

    .setting-card p {
      color: #666;
      margin: 0 0 1.5rem;
      line-height: 1.5;
      font-size: 0.875rem;
    }

    .setting-card a {
      display: inline-flex;
      align-items: center;
      padding: 0.75rem 1.25rem;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 500;
      font-size: 0.875rem;
    }

    .setting-card a:hover {
      background: #0056b3;
    }

    h1 {
      margin: 0 0 1.5rem;
      font-size: 1.5rem;
      color: #333;
    }

    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }
      
      main {
        padding: 1.5rem;
      }

      .settings-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
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
        
        <div class="panel-box">
          <div class="setting-section">
            <h2>テーマ設定</h2>
            <p>サイトのカラーテーマ、ロゴ、背景画像などの見た目をカスタマイズできます。プレビューを見ながら設定が可能です。</p>
            <a href="design.php?page_uid=<?= htmlspecialchars($page_uid) ?>" class="save-button">テーマを設定する</a>
    </div>

          <div class="setting-section">
            <h2>チャット設定</h2>
            <p>チャットボットの性格、初期メッセージ、応答スタイルなどを設定できます。</p>
            <a href="chat.php?page_uid=<?= htmlspecialchars($page_uid) ?>" class="save-button">チャットを設定する</a>
    </div>
    </div>
      </main>
    </div>
    </div>

  <script>
    document.getElementById('sideToggle').addEventListener('click', function() {
      document.getElementById('side_navi').classList.toggle('open');
      document.body.classList.toggle('menu-open');
    });
  </script>
</body>
</html>

<style>
.design-section {
  margin-bottom: 2em;
}
.design-section label {
  display: block;
  font-weight: bold;
  margin-top: 0.5em;
}
.design-section input[type="text"],
.design-section input[type="number"],
.design-section input[type="color"],
.design-section select {
  width: 300px;
  padding: 6px;
  margin-bottom: 0.5em;
}
.design-section input[type="file"] {
  margin-top: 0.3em;
}

.design-section input[type="color"]{
  height: 50px;
  width: 60px
}
button {
  padding: 8px 16px;
  font-weight: bold;
  background-color: #222;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>
