<?php

declare(strict_types=1);
require_once __DIR__ . '/../cros.php';    // ← 先頭に / を付ける

header('Content-Type: application/json; charset=utf-8');

$uid   = $_GET['page_uid'] ?? '';
$q     = $_GET['q']        ?? '';
$tag   = $_GET['tag']      ?? '';
$state = $_GET['state']    ?? '';

$pdo = require dirname(__DIR__).'/../core/db.php';

$sql = 'SELECT id,
               question,
               answer,
               tags,
               pinned,
               hits,
               state,
               updated_at          -- ← 返り値にも欲しければ追加
          FROM question
         WHERE page_uid = :uid
           AND (:st = "" OR state = :st)
           AND (:q  = "" OR MATCH(question) AGAINST(:q IN NATURAL LANGUAGE MODE))
           AND (:tg = "" OR tags LIKE CONCAT("%",:tg,"%"))
      ORDER BY pinned DESC,         -- ① 固定表示
               updated_at DESC,      -- ② 最近編集順
               hits DESC,            -- ③ 人気順
               id   DESC             -- ④ 同時刻の tie-break
      LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':uid'=>$uid, ':st'=>$state, ':q'=>$q, ':tg'=>$tag
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC),
                 JSON_UNESCAPED_UNICODE);
