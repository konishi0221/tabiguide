<?php
/* =======================================================================
   api/chat/ChatService.php ― GPT-4o function-calling ［分割版］
   ======================================================================= */
declare(strict_types=1);

require_once dirname(__DIR__).'/cros.php';
require_once dirname(__DIR__).'/chat/AiClient.php';
require_once dirname(__DIR__).'/chat/DbRepo.php';
require_once dirname(__DIR__).'/chat/HistoryStore.php';
require_once dirname(__DIR__).'/chat/PromptBuilder.php';
require_once dirname(__DIR__).'/chat/CtxStore.php';
require_once dirname(__DIR__).'/chat/save.php';
require_once dirname(__DIR__).'/chat/ToolDefinitions.php';
require_once dirname(__DIR__).'/chat/FaqSearcher.php';
require_once dirname(__DIR__,2).'/public/core/token_usage.php';



class ChatService
{
    
    private string        $pageUid;
    private string        $userId;
    private string        $mode;
    private DbRepo        $repo;
    private HistoryStore  $store;
    private PromptBuilder $prompt;
    private AiClient      $ai;
    private CtxStore      $ctxStore;
    private array         $base;
    private string        $char;
    private array         $extraCache = [];

    public function __construct(string $pageUid, string $userId, string $mode = 'chat')
    {
        $this->pageUid  = $pageUid;
        $this->userId   = $userId;
        $this->mode     = $mode;

        $this->repo     = new DbRepo();
        $this->store    = new HistoryStore($pageUid, $userId);
        $this->ctxStore = new CtxStore($pageUid, $userId);
        $this->ai       = new AiClient();

        $this->base     = $this->repo->fetchBase($pageUid);
        $this->char     = $this->repo->fetchChatCharactor($pageUid);

        $this->prompt   = new PromptBuilder(
            $this->base,
            $this->char,
            dirname(__DIR__).'/prompts'
        );
    }

    /**
     * ユーザー発言を投げて応答を得る
     * - 履歴の読み書きはすべて HistoryStore に任せる
     */
        /** ユーザー発言を投げて応答を得る（最小化版） */
        public function ask(string $userMessage): array
        {
            $mapJson = null;   // ← add to capture map_json if any
            // error_log($userMessage);
            if ($userMessage === '') {
                return ['message' => '', 'via_tool' => false];
            }
    
            /* 1) 履歴に user を追加して保存 */
            $hist      = $this->store->load();
            $hist[]    = ['role' => 'user', 'content' => $userMessage];
            $this->store->save($hist);
            // error_log('[History after user ask] ' . json_encode($hist, JSON_UNESCAPED_UNICODE));
    
            /* 2) system prompt */
            $system    = $this->prompt->build($this->ctxStore->load(), $this->mode);
    
            /* 3) GPT へ渡す messages */
            $messages  = [['role' => 'system', 'content' => $system]];
            foreach (array_slice($hist, -20) as $m) $messages[] = $m;
    
            /* 4) 初回チャット */
            $resp = $this->ai->chat($messages, ToolDefinitions::TOOLS, ['uid' => $this->pageUid]);
    
            /* ChatService ask() : 初回返答受信後すぐ */
            
            /* 5) function-calling ループ */
            $botText = '';
            for ($loop = 0; $loop < 3; $loop++) {
                // --- ループごとのツール構成 ---
                $currentTools = ToolDefinitions::TOOLS;
                if ($loop === 2) {                      // 3 回目は searchFAQ 禁止
                    $currentTools = array_filter(
                        ToolDefinitions::TOOLS,
                        fn($t) => $t['function']['name'] !== 'searchFAQ'
                    );
                    // 3 回目は「質問の言い換え」を促す追加 system
                    $messages[] = [
                        'role'    => 'system',
                        'content' => '2 回試して回答できませんでした。キーワードを言い換えてみて、まだ無理なら saveUnknown を呼んでください。'
                    ];
                }
                $msg = $resp['choices'][0]['message'] ?? [];

                /* function_call → OpenAI v1 uses tool_calls */
                if (isset($msg['tool_calls'][0]['function'])) {
                    $msg['function_call'] = $msg['tool_calls'][0]['function'];
                }

                /* ── 通常の回答で終了 ── */
                if (!isset($msg['function_call'])) {
                    $botText = $msg['content'] ?? '';

                    // If the assistant responded with minified JSON, parse it
                    if ($this->isJson($botText)) {
                        $parsed  = json_decode($botText, true);
                        $mapJson = $parsed['map_json'] ?? null;
                        $botText = $parsed['message']  ?? $botText;
                    } else {
                        $mapJson = $msg['map_json'] ?? null;   // fallback (should be null)
                    }
                    // fallback: detect "text...\n{...}" pattern
                    if ($mapJson === null && preg_match('/\{.*"map_json".*\}$/s', $botText, $m)) {
                        $jsonPart = $m[0];
                        $textPart = substr($botText, 0, -strlen($jsonPart));
                        if ($this->isJson($jsonPart)) {
                            $parsed   = json_decode($jsonPart, true);
                            $mapJson  = $parsed['map_json'] ?? null;
                            $botText  = trim($textPart);
                        }
                    }
                    break;
                }

                $fnName = $msg['function_call']['name'] ?? '';

                /* ── saveUnknown は終端ツール ── */
                if ($fnName === 'saveUnknown') {
                    /* 実行して function メッセージを履歴へ */
                    $this->handleSaveUnknown($hist, json_decode($msg['function_call']['arguments'] ?? '{}', true));
                    $messages[] = end($hist);      // 直近 saveUnknown function

                    /* tools なしで最終回答を取得 */
                    $second = $this->ai->chat($messages, [], ['uid' => $this->pageUid]);
                    $botText = $second['choices'][0]['message']['content'] ?? '';
                    if ($this->isJson($botText)) {
                        $parsed  = json_decode($botText, true);
                        $mapJson = $parsed['map_json'] ?? null;
                        $botText = $parsed['message']  ?? $botText;
                    } else {
                        $mapJson = $second['choices'][0]['message']['map_json'] ?? null;
                    }
                    // fallback: detect "text...\n{...}" pattern for second call
                    if ($mapJson === null && preg_match('/\{.*"map_json".*\}$/s', $botText, $m)) {
                        $jsonPart = $m[0];
                        $textPart = substr($botText, 0, -strlen($jsonPart));
                        if ($this->isJson($jsonPart)) {
                            $parsed   = json_decode($jsonPart, true);
                            $mapJson  = $parsed['map_json'] ?? null;
                            $botText  = trim($textPart);
                        }
                    }
                    break;
                }

                /* ── それ以外のツールは通常処理 ── */
                if ($this->handleCall($hist, $msg) === null) break;

                $messages[] = end($hist);                              // function メッセージ追加
                $resp       = $this->ai->chat($messages, $currentTools, ['uid' => $this->pageUid]);
            }
            
            /* 6) 最終応答保存 */
            // if ($botText === '') $botText = '確認しますのでお待ちください。';
            $hist[] = [
                'role'    => 'assistant',
                'content' => $botText,
                'map_json'=> $mapJson
            ];
            $this->store->save($hist);

        
            //ログ保存
            $this->logChat($hist);   // ask() の assistant 発話保存直後に呼ぶ            

            // error_log($hist);


            return [
                'ctx'     => $this->ctxStore->load(),
                'message' => $botText,
                'via_tool'=> true,
                'map_json'=> $mapJson
            ];
        }
    

    /* =======================================================
       DB slot 取得
       ======================================================= */
    private function handleToolCall(string $slot): string
    {
        if (isset($this->extraCache[$slot])) {
            return json_encode($this->extraCache[$slot],JSON_UNESCAPED_UNICODE);
        }

        if ($slot==='nearby_stores') {
            $st=$this->repo->pdo()->prepare('SELECT * FROM stores WHERE facility_uid=?');
            $st->execute([$this->pageUid]);
            $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: ['error'=>'NOT_FOUND'];
            return json_encode($this->extraCache[$slot]=$rows,JSON_UNESCAPED_UNICODE);
        }

        $map=[
            'location'=>['facility_ai_data','contact_data,geo_data,location_notes'],
            'stay'    =>['facility_ai_data','stay_data'],
            'rule'    =>['facility_ai_data','rule_data,rule_notes'],
            'amenity' =>['facility_ai_data','amenities_data,amenities_notes'],
            'service' =>['facility_ai_data','services_data'],
            'contact' =>['facility_ai_data','contact_data']
        ];
        if(!isset($map[$slot])) return '{"error":"invalid slot"}';

        [$tbl,$cols]=$map[$slot];
        $st=$this->repo->pdo()->prepare("SELECT $cols FROM $tbl WHERE page_uid=? LIMIT 1");
        $st->execute([$this->pageUid]);
        $row=$st->fetch(PDO::FETCH_ASSOC) ?: ['error'=>'NOT_FOUND'];

        foreach($row as $k=>$v) if(is_string($v)&&$this->isJson($v)) $row[$k]=json_decode($v,true);
        return json_encode($this->extraCache[$slot]=$row,JSON_UNESCAPED_UNICODE);
    }


    private function logChat(array $hist): void
    {
        $pair = array_slice($hist, -2);                       // user + assistant
        save_chat_log([
            'chat_id'     => $this->userId,
            'page_uid'    => $this->pageUid,
            'room_id'     => $this->ctxStore->load()['room_id'] ?? null,
            'conversation'=> json_encode($pair, JSON_UNESCAPED_UNICODE) // 1 ターン分
        ]);
    }

    /** 履歴取得 */
    public function getHistory(): array
    {
        return $this->store->load();
    }

    private function handleCall(array &$hist, array $msg): ?string
    {
        $name = $msg['function_call']['name'] ?? '';
        $args = json_decode($msg['function_call']['arguments'] ?? '{}', true);
    
        return match ($name) {
            'updateCtx'   => $this->handleUpdateCtx($hist, $args),
            'searchFAQ'   => $this->handleSearchFAQ($hist, $args),
            'saveUnknown' => $this->handleSaveUnknown($hist, $args),
            'notifyStaff' => $this->handleNotifyStaff($hist, $args),
            default       => null
        };
    }

    /* それぞれ細かい処理を小メソッドへ */
    private function handleUpdateCtx(array &$hist, array $args): string
    {
        $ctx = $this->ctxStore->merge($args);
        $hist[] = ['role'=>'function','name'=>'updateCtx',
                'content'=>json_encode($ctx,JSON_UNESCAPED_UNICODE)];
        $this->store->save($hist);
        return end($hist)['content'];  // GPT への返却用
    }

    private function handleSearchFAQ(array &$hist, array $args): string
    {
        $kw   = $args['keywords'] ?? '';
        $rows = FaqSearcher::search($this->repo->pdo(), $this->pageUid, $kw);

        // decode map_json string → array for each row
        foreach ($rows as &$r) {
            if (isset($r['map_json']) && is_string($r['map_json']) && $this->isJson($r['map_json'])) {
                $r['map_json'] = json_decode($r['map_json'], true);
            }
        }
        unset($r); // break reference

        $json  = json_encode($rows, JSON_UNESCAPED_UNICODE);
        $hist[] = ['role' => 'function', 'name' => 'searchFAQ', 'content' => $json];
        $this->store->save($hist);
        return $json;
    }

    /* 未実装分はとりあえず空 JSON 返しで OK */
    private function handleSaveUnknown(array &$hist, array $a): string
    {
        /* 1) DB へ未知質問を保存 */
        save_unknown(
            $this->repo->pdo(),        // PDO
            $this->pageUid,            // page_uid
            $this->userId,             // chat_id
            $a['question'] ?? '',      // 質問文
            $a['tag']      ?? ''       // タグ
        );
    
        /* 2) function メッセージを履歴に追加 */
        $hist[] = ['role'=>'function','name'=>'saveUnknown','content'=>'{}'];
        $this->store->save($hist);    
        return '{}';
    }

    private function handleNotifyStaff(array &$hist, array $a): string
    {
        $a['page_uid'] = $this->pageUid;
        $a['user_id']  = $this->userId;
        save_staff($a);
        $hist[] = ['role'=>'function','name'=>'notifyStaff','content'=>'{}'];
        $this->store->save($hist);
        return '{}';
    }

    /* ---------- util ---------- */
    private function isJson(string $s): bool
    {
        json_decode($s); return json_last_error()===JSON_ERROR_NONE;
    }
}
