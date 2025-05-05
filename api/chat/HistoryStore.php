<?php
/* =======================================================================
   api/chat/HistoryStore.php ― 会話履歴ストア
   ======================================================================= */
declare(strict_types=1);

class HistoryStore
{
    private string $key;      // セッションキー
    private int    $limit = 40; // 保存件数上限

    public function __construct(string $pageUid, string $userId)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->key = "chat_{$pageUid}_{$userId}";
        error_log($this->key);

    }

    /** 履歴を返す */
    public function load(): array
    {
        return $_SESSION[$this->key] ?? [];
    }

    /** 履歴を保存。最新 $limit 件だけ保持 */
    public function save(array $hist): void
    {
        $_SESSION[$this->key] = array_slice($hist, -$this->limit);
    }
}
