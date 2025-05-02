<?php
declare(strict_types=1);
require_once __DIR__.'/../core/config.php';
require_once __DIR__.'/../core/db.php';

session_start();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - TabiGuide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- サイドバー -->
            <div class="col-md-3 col-lg-2">
                <h3>TabiGuide</h3>
                <div class="list-group">
                    <a href="/admin/api-usage/" class="list-group-item list-group-item-action">API使用量確認</a>
                    <a href="/admin/facilities/" class="list-group-item list-group-item-action">施設情報</a>
                    <a href="/admin/users/" class="list-group-item list-group-item-action">ユーザー情報</a>
                    <a href="/admin/prompts/" class="list-group-item list-group-item-action">プロンプト更新</a>
                    <a href="/logout/" class="list-group-item list-group-item-action text-danger">ログアウト</a>
                </div>
            </div>

            <!-- メインコンテンツ -->
            <div class="col-md-9 col-lg-10">
                <h2>ダッシュボード</h2>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
