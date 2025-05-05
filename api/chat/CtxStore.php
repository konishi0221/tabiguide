<?php
class CtxStore
{
    private string $key;
    public function __construct(string $pageUid, string $userId)
    {
        $this->key = "ctx_{$pageUid}_{$userId}";
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
    
    public function load(): array
    {
        return $_SESSION[$this->key] ?? [];
    }
    public function save(array $ctx): void
    {
        $_SESSION[$this->key] = $ctx;
    }
    /** 既存 ctx に差分マージ */
    public function merge(array $patch): array
    {
        $ctx = $this->load();
        $ctx = array_merge($ctx, $patch);
        $this->save($ctx);
        return $ctx;
    }
}
