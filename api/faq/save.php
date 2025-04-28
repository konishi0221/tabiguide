<?php
declare(strict_types=1);
require_once __DIR__ . '/../cros.php';    // ← 先頭に / を付ける

header('Content-Type: application/json; charset=utf-8');
$req  = json_decode(file_get_contents('php://input'), true) ?? [];
$mode = $req['mode'] ?? '';

$root = dirname(__DIR__, 2);        // /workspace
$pdo  = require $root . '/public/core/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try{
  switch($mode){
    case 'toggle_pin':
      $pdo->prepare('UPDATE question SET pinned=NOT pinned WHERE id=?')
          ->execute([$req['id']]);
      break;

    case 'delete':
      $pdo->prepare('DELETE FROM question WHERE id=?')
          ->execute([$req['id']]);
      break;

    case 'create':
      $pdo->prepare(
        'INSERT INTO question(page_uid,question,answer,tags,state)
         VALUES(:u,:q,:a,:t,"draft")'
      )->execute([
        ':u'=>$req['page_uid'], ':q'=>$req['question'],
        ':a'=>$req['answer']??'', ':t'=>$req['tags']??''
      ]);
      echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
      exit;

      case 'update':
        $sql = 'UPDATE question
                  SET question = :q,
                      answer   = :a,
                      tags     = :t,
                      state    = CASE WHEN LENGTH(:ans) > 0 THEN "reply" ELSE state END
                WHERE id      = :id';

        $pdo->prepare($sql)->execute([
            ':q'  => $req['question'],
            ':a'  => $req['answer'],
            ':ans'=> $req['answer'],   // ← 判定用に別名
            ':t'  => $req['tags'],
            ':id' => $req['id']
        ]);
        echo '{"ok":true}';
        exit;

      case 'archive':
        $pdo->prepare('UPDATE question SET state="archive" WHERE id=?')
            ->execute([$req['id']]);
        break;

      case 'unarchive':
        $pdo->prepare('UPDATE question SET state="reply" WHERE id=?')
            ->execute([$req['id']]);
        break;


    default:
      throw new RuntimeException('invalid mode');
  }
  echo '{"ok":true}';
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
