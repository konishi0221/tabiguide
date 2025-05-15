<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/core/bootstrap.php';
$data = json_decode(file_get_contents('php://input'), true);
$pdo  = require dirname(__DIR__,2).'/core/db.php';

$map_json = json_encode([
  'map'  => $data['map_key'],
  'pins' => $data['pins']
], JSON_UNESCAPED_UNICODE);

$st = $pdo->prepare('UPDATE question SET map_json=? WHERE page_uid=? LIMIT 1');
$st->execute([$map_json, $data['page_uid']]);