<?php
/* =======================================================================
   api/chat/FaqSearcher.php
   ─ ベクトル検索 ＋ FULLTEXT フォールバック
   ======================================================================= */
declare(strict_types=1);

require_once dirname(__DIR__).'/chat/AiClient.php';   // embedding 用

class FaqSearcher
{
    /* ---------------- パブリック ---------------- */
    public static function search(PDO $pdo, string $pageUid, string $query): ?array
    {
        $query = trim($query);
        if ($query === '') return null;

        /* 1) ベクトル検索を試す -------------- */
        $rows = self::vectorSearch($pdo, $pageUid, $query);
        if ($rows) return $rows;                     // ヒットがあれば確定
        
        /* 2) ヒット薄なら FULLTEXT へ ---------- */
        return self::fulltextSearch($pdo, $pageUid, $query);
    }

    /* ---------------- ベクトル検索 ---------------- */
    private static function vectorSearch(PDO $pdo, string $uid, string $q): ?array
    {

        

        $vec = self::embedding($q);                  // 1536 次元 array
        if (!$vec) return null;
        
        $st = $pdo->prepare(
            'SELECT id,question,answer,tags,embedding
               FROM question
              WHERE page_uid=? AND state<>"archive"
                AND LENGTH(TRIM(answer))>0'
        );
        $st->execute([$uid]);
        $hits = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if ($row['embedding'] === null) continue;

            // error_log('[null emb] '.$row['id']);     // ← embedding が空
            
            

            $e = json_decode($row['embedding'], true) ?: [];
            if (count($e) !== count($vec)) continue;

            $score = self::cosine($vec, $e);

            if ($score > 0.5) {                     // 閾値は適宜
                $row['score'] = $score;
                unset($row['embedding']);         // ← 追加
                $hits[] = $row;
            }
        }


        usort($hits, fn($a,$b)=>$b['score']<=>$a['score']);
        return array_slice($hits, 0, 3) ?: null;     // 上位3件
    }

    /* ---------------- FULLTEXT 検索 ---------------- */
    private static function fulltextSearch(PDO $pdo, string $uid, string $q): ?array
    {

        $sql = 'SELECT id,question,answer,tags,hits
                  FROM question
                 WHERE page_uid=:uid AND state<>"archive"
                   AND LENGTH(TRIM(answer))>0
                   AND MATCH(question,tags) AGAINST(:ft IN NATURAL LANGUAGE MODE)
                 ORDER BY hits DESC, pinned DESC, updated_at DESC
                 LIMIT 3';
        $st = $pdo->prepare($sql);
        $st->execute([':uid'=>$uid, ':ft'=>$q]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        /* ヒット数カウント */
        if ($rows) {
            $ids = array_column($rows, 'id');
            $ph  = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("UPDATE question SET hits=hits+1 WHERE id IN ($ph)")
                ->execute($ids);
        }
        return $rows ?: null;
    }

    /* ---------------- util ---------------- */
    private static function embedding(string $text): ?array
    {
        try {
            $ai  = new AiClient();
            $res = $ai->embeddings('text-embedding-3-small', $text);
            return $res['data'][0]['embedding'] ?? null;
        } catch (\Throwable $e) {
            error_log('[embed] '.$e->getMessage());
            return null;
        }
    }

    private static function cosine(array $a, array $b): float
    {
        $dot = $na = $nb = 0.0;
        foreach ($a as $i=>$v) {
            $dot += $v * $b[$i];
            $na  += $v * $v;
            $nb  += $b[$i] * $b[$i];
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }
}
