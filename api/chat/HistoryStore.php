<?php
/* =======================================================================
   api/chat/HistoryStore.php ― 会話履歴ストア
   ======================================================================= */
declare(strict_types=1);

class HistoryStore
{
    /** ページ UID (課金用) */
    private string $pageUid;

    private string $key;      // セッションキー
    private int    $limit = 40; // 保存件数上限

    /** 圧縮開始閾値（これを超えたら要約する） */
    private const THRESHOLD = 10;       // 10 往復
    /** 直近に残す生メッセージ数 */
    private const KEEP_RECENT = 6;     // 3 往復

    /** 軽量 GPT クライアント（要 summarise） */
    private AiClient $aiMini;

    public function __construct(string $pageUid, string $userId)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pageUid = $pageUid;
        $this->key = "chat_{$pageUid}_{$userId}";

        // 軽量モデルで要約用
        require_once dirname(__DIR__) . '/chat/AiClient.php';
        require_once dirname(dirname(__DIR__)) . '/public/core/token_usage.php';
        $this->aiMini = new AiClient('gpt-4o-mini');
    }

    /** 履歴を返す */
    public function load(): array
    {
        return $_SESSION[$this->key] ?? [];
    }

    /** 履歴を保存し、必要なら古い部分を要約に置き換える */
    public function save(array $hist): void
    {
        // 1) 閾値を超えなければそのまま
        if (count($hist) <= self::THRESHOLD) {
            $_SESSION[$this->key] = array_slice($hist, -$this->limit);
            return;
        }

        // --- Split into RECENT (直近 KEEP_RECENT 件) と OLDER (それ以前) ----
        $recent = array_slice($hist, -self::KEEP_RECENT);
        $older  = array_slice($hist, 0, -self::KEEP_RECENT);

        /* もし recent[0] が前回のサマリーなら
           1) それも OLDER に加えて再要約してもらう
           2) recent からは外し、代わりに raw メッセージを 1 件多く残す */
        if ($recent &&
            $recent[0]['role'] === 'system' &&
            str_starts_with($recent[0]['content'] ?? '', '【過去の会話要約】')
        ) {
            $older[] = array_shift($recent);            // move previous summary into OLDER
            // 補充: KEEP_RECENT+1件残すことで合計件数は変わらない
            $replacement = array_slice($hist, -self::KEEP_RECENT-1, 1);
            $recent = array_merge($replacement, $recent);
        }

        // 3) 既に summary があれば追加
        $summaryTxt = $this->summarize($older);

        // 4) 要約を system メッセージとして先頭に挿入
        $compressed = $recent;
        if ($summaryTxt !== '') {
            $summaryMsg = [
                'role'    => 'system',
                'content' => '【過去の会話要約】' . $summaryTxt
            ];
            array_unshift($compressed, $summaryMsg);
        }

        // 5) 保存（上限カット）
        $_SESSION[$this->key] = array_slice($compressed, -$this->limit);
    }


    /**
     * 指定メッセージ配列を段階的に要約して **200 文字以内** に圧縮
     * - 1チャンク 3,000 文字で分割
     * - 各チャンクを gpt-4o-mini で300文字要約
     * - 連結して最終 200 文字要約
     **/
    private function summarize(array $msgs): string
    {
        // --- 平文化 ---
        $plain = '';
        foreach ($msgs as $m) {
            if (empty($m['content'])) continue;
            // user / assistant のみ採用
            if ($m['role'] !== 'user' && $m['role'] !== 'assistant') continue;
            $name = $m['role']==='user' ? 'ゲスト' : 'Bot';
            $plain .= "{$name}: {$m['content']}\n";
        }
        if ($plain === '') return '';

        // ---- chunk split (3k chars) ----
        $chunks = str_split($plain, 3000);
        $subSummaries = [];

        foreach ($chunks as $chunk) {
            $subSummaries[] = $this->gptSummary($chunk, 300);
        }

        // ---- final summary ----
        $joined = implode("\n", $subSummaries);
        $final  = $this->gptSummary($joined, 200);

        // fallback if even final is empty
        if ($final === '') {
            $final = mb_substr(str_replace(["\r","\n"], ' ', $plain), 0, 200);
        }

        return $final;
    }

    /** helper that asks mini model for summaryLen 文字以内要約 */
    private function gptSummary(string $text, int $summaryLen): string
    {
        $sys = "以下のテキストを {$summaryLen} 文字以内で日本語で要約してください。";
        $res = $this->aiMini->chat(
            [
                ['role'=>'system','content'=>$sys],
                ['role'=>'user',  'content'=>$text]
            ],
            [],
            ['uid'=>$this->pageUid]
        );

        // cost accounting
        if (isset($res['usage']['prompt_tokens'])) {
            chargeGPT(
                $this->pageUid,
                'gpt-4o-mini',
                $res['usage']['prompt_tokens']     ?? 0,
                $res['usage']['completion_tokens'] ?? 0
            );
        }

        $out = trim($res['choices'][0]['message']['content'] ?? '');

        return $out;
    }
}
