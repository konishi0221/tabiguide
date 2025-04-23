<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
if (!$page_uid) {
    echo "page_uid is required";
    exit;
}

// 部屋データ取得
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE page_uid = ? ORDER BY id ASC");
$stmt->execute([$page_uid]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

global $facility_type;

$roomLabel = getRoomLabel($facility_type);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($roomLabel) ?>一覧</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <style>
  .room-list-container {
    margin-top: 2rem;
  }

  .room-card {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  }

  .room-card:hover {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  }

  .room-title {
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #333;
  }

  .room-info {
    font-size: 0.95rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .room-actions {
    margin-top: 0.5rem;
  }

  .room-actions form {
    display: inline-block;
    margin-right: 0.5rem;
  }

  .room-actions button {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
  }

  .room-actions .edit-button {
    background-color: #1976d2;
    color: #fff;
  }

  .room-delete-icon {
    background-color: white;
    position: absolute;
    bottom: 12px;
    right: 12px;
    color: #e53935;
    font-size: 20px;
    cursor: pointer;
    transition: color 0.2s ease;
  }

  .room-delete-icon:hover {
    color: #b71c1c;
    background-color: rgba(0,0,0,0.2);

  }
  </style>
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app">
  <main>
    <h1><?= htmlspecialchars($roomLabel) ?>一覧</h1>
    <a href="create.php?page_uid=<?= htmlspecialchars($page_uid) ?>" class="button">＋ <?= htmlspecialchars($roomLabel) ?>を追加</a>

    <div class="room-list-container">
      <?php foreach ($rooms as $room): ?>
        <div class="room-card">
          <div class="room-title"><?= htmlspecialchars($room['room_name']) ?></div>
          <div class="room-info">定員: <?= $room['capacity'] ?>人</div>
          <div class="room-info"><?= htmlspecialchars($roomLabel) ?>タイプ: <?= htmlspecialchars($room['room_type']) ?></div>
          <div class="room-actions">
            <a href="create.php?page_uid=<?= $page_uid ?>&room_uid=<?= $room['room_uid'] ?>">
              <button class="edit-button">編集</button>
            </a>
            <form method="post" action="delete.php" onsubmit="return confirm('本当に削除しますか？')">
              <input type="hidden" name="page_uid" value="<?= $page_uid ?>">
              <input type="hidden" name="room_uid" value="<?= $room['room_uid'] ?>">
              <button type="submit" class="room-delete-icon">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($rooms) === 0): ?>
      <p>まだ<?= htmlspecialchars($roomLabel) ?>が登録されていません。</p>
    <?php endif; ?>
  </main>
</div>

</div>
</body>
</html>
