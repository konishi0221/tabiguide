<?php
// /api/tools/embed_faq.php
declare(strict_types=1);

$root = dirname(__DIR__);               // /workspace
require_once $root.'/public/core/db.php';
require_once $root.'/api/chat/AiClient.php';

function makeEmbedding(string $text): string {
    $ai  = new AiClient();
    $res = $ai->embeddings('text-embedding-3-small', $text);
    return json_encode($res['data'][0]['embedding'] ?? [], JSON_UNESCAPED_UNICODE);
}

$pdo = require $root.'/public/core/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $pdo->query(
    'SELECT id, question FROM question WHERE embedding IS NULL'
)->fetchAll(PDO::FETCH_ASSOC);

echo "対象 ".count($rows)." 件\n";

foreach ($rows as $r) {
    $vec = makeEmbedding($r['question']);
    $pdo->prepare('UPDATE question SET embedding=? WHERE id=?')
        ->execute([$vec, $r['id']]);
    echo "id ".$r['id']." … done\n";
    usleep(200_000);        // 0.2s スロットル（API 制限対策）
}
echo "完了\n";



