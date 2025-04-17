<?php
require_once __DIR__ . '/core/facility_template.php' ; // ← PHPから読み込む
// $encodedImages = resize_and_encode_images($files);
$template = loadFacilityTemplate('ryokan');
echo '<pre>' . htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
