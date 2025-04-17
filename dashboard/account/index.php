<?php
// session_start();
require_once dirname(__DIR__) . '/../core/dashboard_head.php';
$user = $_SESSION['user'];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ダッシュボード</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <style>
  .account-container {
    max-width: 600px;
    margin: 60px auto;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 32px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.04);
  }

  .account-container h2 {
    margin-bottom: 24px;
    font-size: 20px;
  }

  .account-container label {
    display: block;
    font-weight: bold;
    margin: 16px 0 6px;
  }

  .account-container input[type="text"],
  .account-container input[type="email"] {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
  }

  .account-container input[type="file"] {
    margin-top: 8px;
  }

  .account-container .icon-preview {
    display: inline-block;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    vertical-align: middle;
    margin-right: 12px;
  }

  .account-container button {
    margin-top: 24px;
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
  }

  .account-container button:hover {
    background: #0069d9;
  }

  </style>
</head>
<body>
  <?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>

<div id="app" class="container">
  <!-- <main> -->

    <form method="POST" action="/dashboard/account/save_user.php" enctype="multipart/form-data">
      <div class="account-container">
        <h2>アカウント情報の編集</h2>

        <form method="POST" action="/dashboard/account/save_user.php" enctype="multipart/form-data">
          <label>アイコン画像</label>
          <img :src="user.iconPreview || user.icon" class="icon-preview" alt="アイコン">
          <input type="file" name="icon" @change="handleIconChange">

          <label>ユーザー名</label>
          <input type="text" name="name" v-model="user.name">

          <label>メールアドレス</label>
          <input type="email" name="email" v-model="user.email">

          <button type="submit">保存する</button>
        </form>
      </div>
    </form>
  <!-- </main> -->
</div>

<script>
const { createApp } = Vue;
createApp({
  data() {
    return {
      user: {
        name: <?= json_encode($user['name'] ?? ''); ?>,
        email: <?= json_encode($user['email'] ?? ''); ?>,
        icon: <?= json_encode(isset($user['icon_base64']) && $user['icon_base64'] ? 'data:image/png;base64,' . $user['icon_base64'] : '/assets/images/default_icon.png'); ?>,
        iconPreview: null
      }
    }
  },
    methods: {
      handleIconChange(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = e => {
            this.user.iconPreview = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      }
    }
}).mount('#app');
</script>
</body>
</html>
