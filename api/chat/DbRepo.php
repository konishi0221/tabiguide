<?php
class DbRepo
{
    private PDO $db;
    public function __construct()
    {
        /* core/db.php は PDO を return する            */
        /* api/chat から見て ../../public/core/db.php   */
        $this->db = require dirname(dirname(__DIR__)).'/public/core/db.php';
    }
    public function pdo(): PDO
    {
        return $this->db;
    }
    public function fetchBase(string $pageUid): array
    {
        $st = $this->db->prepare(
            'SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1'
        );
        $st->execute([$pageUid]);
        return json_decode($st->fetchColumn() ?: '{}', true);
    }
    
    public function fetchChatCharactor(string $pageUid): string
    {
        $st = $this->db->prepare(
            'SELECT chat_charactor FROM design WHERE page_uid = ? LIMIT 1'
        );
        $st->execute([$pageUid]);
        return $st->fetchColumn() ?: '';
    }
    
}
