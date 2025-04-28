<?php
declare(strict_types=1);
require_once __DIR__ . '/../cros.php';    // ← 先頭に / を付ける

header('Content-Type: application/json; charset=utf-8');

$req = json_decode(file_get_contents('php://input'), true) ?? [];
require_once dirname(__DIR__) . '/../core/db.php';
$pdo = require dirname(__DIR__) . '/../core/db.php';

$pdo->prepare('DELETE FROM question WHERE id=?')->execute([$req['id']]);
echo '{"ok":true}';
