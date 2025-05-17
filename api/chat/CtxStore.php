<?php
class CtxStore
{
    /** デフォルトコンテキスト */
    private const DEFAULT_CTX = [
        'lang'      => '',
        'room_name'   => null,
        'room_uid'   => null,
        'stage' => null,
        'profiling' => ''           // ゲスト人物像（200文字以内）
    ];

    private string $key;
    public function __construct(string $pageUid, string $userId)
    {
        $this->key = "ctx_{$pageUid}_{$userId}";
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION[$this->key])) {
            $_SESSION[$this->key] = self::DEFAULT_CTX;
        }
    }
    
    public function load(): array
    {
        return $_SESSION[$this->key] ?? self::DEFAULT_CTX;
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
    /** profiling を最大 200 文字で保存 */
    public function saveProfiling(string $profile): void
    {
        $ctx = $this->load();
        $summary = mb_substr($profile, 0, 200);
        $ctx['profiling'] = $summary;
        $ctx['profile']   = $summary;   // alias 同期
        $this->save($ctx);
    }

    /** @deprecated: 今後は saveProfiling を使用 */
    public function saveProfile(string $profile): void
    {
        $this->saveProfiling($profile);
    }
    /** ctx を丸ごと削除（セッションから除去） */
    public function clear(): void
    {
        unset($_SESSION[$this->key]);
    }
}
