<?php
require_once __DIR__.'/../../../vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/core/db.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$page_uid = $_GET['page_uid'] ?? '';
$room_uid = $_GET['room_uid'] ?? '';
$room_name = '';

$stmt = $pdo->prepare("SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
$stmt->execute([$page_uid]);
$json = json_decode($stmt->fetchColumn() ?: '[]', true);
$facility_name = $json['施設名'] ?? '';

if ($room_uid) {
    $stmtRoom = $pdo->prepare("SELECT room_name FROM rooms WHERE room_uid = ? LIMIT 1");
    $stmtRoom->execute([$room_uid]);
    $room_name = $stmtRoom->fetchColumn() ?: '';
}

$url = $room_uid
    ? "https://app.tabiguide.net/{$page_uid}/?room={$room_uid}"
    : "https://app.tabiguide.net/{$page_uid}/";
$qr   = (new PngWriter())->write(new QrCode($url))->getDataUri();
$logoUrl = "https://storage.googleapis.com/tabiguide_uploads/upload/{$page_uid}/images/header_logo.png";
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>Poster</title>
<style>
@media print { body{ margin:0 } }
@page{
  size: A4 portrait;
  margin: 0;
}
body{
  background:#f5f5f5;            /* whitesmoke */
  font-family:"Noto Sans JP",sans-serif;
  margin:0;
  display:flex;
  justify-content:center;
  align-items:center;
  height:100vh;
}
.wrapper{
  width:210mm;
  height:297mm;
  /* display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:8mm; */
  background:#fff;
  box-shadow:0 0 12px rgba(0,0,0,.15);
  position: relative;
  text-align: center;
}
.logo{
  width:50mm;
  height:50mm;
  object-fit:contain;
  top: 40mm;
  position:absolute;
  transform: translateY(-50%) translateX(-50%);
    -webkit-transform: translateY(-50%) translateX(-50%);
}
.room-name{
    font-size:18pt;
  font-weight:500;
  /* margin-top: 20mm; */
  position:absolute;
  transform: translateY(-50%) translateX(-50%);
    -webkit-transform: translateY(-50%) translateX(-50%);
    left: 50%;
  top: 50mm;
}
h1{ letter-spacing:6px; margin:0; font-size:36pt }
p{ margin:0; font-size:16pt }
img{ width:80mm; height:80mm }
#print-btn {
  position:fixed;
  top:12px; right:12px;
  padding:8px 16px;
  font-size:14px;
  background:#007bff;
  color:#fff;
  border:none;
  border-radius:4px;
  cursor:pointer;
}
@media print{
  #print-btn{display:none}
  body{ background:#fff; display:block; height:auto; }
  .wrapper{ box-shadow:none; }
}
.headline{
  font-size:40pt;
  font-weight:700;
  letter-spacing:4px;
  margin:0;
  position:absolute;
    top: 95mm;
    left: 50%;
    transform: translateY(-50%) translateX(-50%);
    -webkit-transform: translateY(-50%) translateX(-50%);
    letter-spacing: 10px;
}
.sub{
  font-size:14pt;
  text-align:center;
  line-height:1.4;
  margin:0;
  position:absolute;
  bottom: 70mm;
  width: 100%;
}

.sub_en{
  font-size:14pt;
  text-align:center;
  line-height:1.4;
  margin:0;
  position:absolute;
  bottom: 55mm;
  width: 100%;
}

.thanks{
  font-size:18pt;
  font-weight:600;
  margin-top:8mm;
}
.qr{
  width:80mm;
  height:80mm;
  object-fit:contain;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translateY(-50%) translateX(-50%);
  -webkit-transform: translateY(-50%) translateX(-50%);
}
.facility {
  margin-top: 20mm;
  font-size: 20pt;
  /* margin: 8mm 0 0;
  font-weight: 500; */
}
</style>
</head>
<body>
<button id="print-btn" onclick="window.print()">印刷</button>
  <div class="wrapper">
    
    <?php if ($logoUrl): ?>
      <img class="logo" src="<?= $logoUrl ?>" alt="logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
    <?php endif; ?>

    <?php if ($room_uid && $room_name): ?>
      <h3 class="room-name"><?= htmlspecialchars($room_name) ?></h3>
    <?php endif; ?>

    <?php if ($facility_name): ?>
      <h2 class="facility" style="display:none;"><?= htmlspecialchars($facility_name) ?></h2>
    <?php endif; ?>

    <h2 class="headline">SCAN&nbsp;ME!</h2>

    <img class="qr" src="<?= $qr ?>" alt="QR">

    <p class="sub">AIチャットでのご相談やスタッフへのご連絡、<br>周辺マップの確認など、こちらからご利用いただけます。</p>
    <p class="sub_en">Multilingual support AI Chat / Staff Call / Local Map</p>
    
  </div>
</body>
</html>