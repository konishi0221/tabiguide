<?php
require_once dirname(__DIR__, 2) . '/core/dashboard_head.php';

$page_uid = $_GET['page_uid'] ?? '';
$room_uid = $_GET['room_uid'] ?? null;

global $facility_type;

$roomLabel = htmlspecialchars(getRoomLabel($facility_type));


$isEdit = !empty($room_uid);
$room = [
    'room_name' => '',
    'room_type' => '',
    'capacity' => '',
    'notes' => ''
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_uid = ? AND page_uid = ? LIMIT 1");
    $stmt->execute([$room_uid, $page_uid]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        die("指定された部屋が見つかりません");
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= $roomLabel ?><?= $isEdit ? '編集' : '作成' ?></title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

  <div id="app">
    <main>
      <h1><?= $roomLabel ?><?= $isEdit ? '編集' : '作成' ?></h1>

      <form method="post" action="complete.php">
        <input type="hidden" name="mode" value="<?= $isEdit ? 'update' : 'insert' ?>">
        <input type="hidden" name="page_uid" value="<?= htmlspecialchars($page_uid) ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="room_uid" value="<?= htmlspecialchars($room['room_uid']) ?>">
        <?php endif; ?>

        <label><?= $roomLabel ?>名</label><br>
        <input type="text" name="room_name" value="<?= htmlspecialchars($room['room_name']) ?>" placeholder="<?= $facility_type == "キャンプ場" ? 'グランピング①' : '202号室' ?>" required><br><br>

        <label><?= $roomLabel ?>タイプ</label><br>
        <input type="text" name="room_type" value="<?= htmlspecialchars($room['room_type']) ?>" placeholder="<?= $facility_type == "キャンプ場" ? 'グランピング, キャンプサイト' : '洋室' ?>"><br><br>

        <label>定員（人数）</label><br>
        <input type="number" name="capacity" value="<?= htmlspecialchars($room['capacity']) ?>" min="0" placeholder="<?= $facility_type == "キャンプ場" ? '5' : '5' ?>" ><br><br>

        <label>補足情報</label><br>
        <textarea name="notes" placeholder="<?= $facility_type == "キャンプ場" ? '車の乗り入れ禁止' : '喫煙可能' ?>" rows="5"><?= htmlspecialchars($room['notes']) ?></textarea><br><br>

        <button type="submit"><?= $isEdit ? '更新する' : '作成する' ?></button>
      </form>
    </main>
  </div>
</div>
</body>
</html>
