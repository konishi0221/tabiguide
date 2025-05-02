<?php
/* =======================================================================
   ChatService.php ― GPT-4o function-calling 完全版（multi-slot＋FAQ＋スタッフ連携）
   ======================================================================= */
declare(strict_types=1);
require_once dirname(__DIR__).'/cros.php';
require_once dirname(dirname(__DIR__)).'/public/core/config.php';
require_once dirname(dirname(__DIR__)).'/public/core/db.php';
require_once dirname(__DIR__).'/save.php';

class ChatService
{
    /* ------------ プロパティ ------------ */
    private ?PDO   $db   = null;
    private array  $hist = [];          // user / assistant / function
    private array  $base = [];          // facility_ai_data.base_data
    private array  $extraCache = [];    // slot 取得済みキャッシュ
    private string $pageUid;
    private string $userId;
    private string $apiKey;             // ← ctor で決定

    /* ------------ ctor ------------ */
    public function __construct(string $pageUid, string $userId)
    {
        $this->pageUid = $pageUid;
        $this->userId  = $userId;
        $this->apiKey  = defined('OPENAI_API_KEY')
                         ? OPENAI_API_KEY
                         : (getenv('OPENAI_API_KEY') ?: '');

        /* セッション履歴ロード（最大 40 件） */
        $this->hist = $_SESSION["chat_{$pageUid}_{$userId}"] ?? [];

        /* base_data だけ先に取得 */
        $stmt = $this->db()->prepare(
            'SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1'
        );
        $stmt->execute([$pageUid]);
        $this->base = json_decode($stmt->fetchColumn() ?: '{}', true);
    }

    /* ------------ 履歴アクセサ ------------ */
    public function getHistory(): array { return $this->hist; }

    private function saveHistory(): void
    {
        if ($this->hist) {
            $_SESSION["chat_{$this->pageUid}_{$this->userId}"] = array_slice($this->hist, -40);
        }
    }

/* ---------- ChatService.php ---------- */
    private function buildSystemPrompt(): string
    {
        // ChatService.php が api/chat/ にある想定:
        //   /workspace/api/chat/ChatService.php
        //   /workspace/api/prompts/chat_system.txt
        $path = dirname(__DIR__).'/prompts/chat_system.txt';

        $tmpl = is_file($path) ? file_get_contents($path) : '';
        if ($tmpl === false) $tmpl = '';

        /* セッション ctx と base_data を JSON で付加 */
        $ctx  = $_SESSION["ctx_{$this->pageUid}_{$this->userId}"] ?? [];

        return $tmpl
            ."\n\n<!--base_data-->\n".json_encode($this->base, JSON_UNESCAPED_UNICODE)
            ."\n\n<!--guest_ctx-->\n".json_encode($ctx,        JSON_UNESCAPED_UNICODE);
    }

    /* =========================================================
       ask() ― GPT-4o + function-calling（3ターン上限、saveUnknown非カウント）
       ========================================================= */
       public function ask(string $userMessage, array $ctx = []): array
       {
           if ($userMessage === '') {
               return ['message' => '', 'via_tool' => false];
           }
   
           /* ------ ctx から追加履歴をマージ ------ */
           if (!empty($ctx['messages']) && is_array($ctx['messages'])) {
               $add = array_map(static function ($m) {
                   if (isset($m['function_call'])) {
                       return ['role' => 'assistant', 'function_call' => $m['function_call']];
                   }
                   return [
                       'role'    => ($m['role'] === 'bot' ? 'assistant' : 'user'),
                       'content' => $m['text'] ?? ''
                   ];
               }, $ctx['messages']);
   
               while ($add && $this->hist &&
                      end($add)['role']    === end($this->hist)['role'] &&
                      end($add)['content'] === end($this->hist)['content']) {
                   array_pop($add);
               }
               $this->hist = array_merge($this->hist, $add);
           }
   
           /* ------ 最新 user を履歴へ ------ */
           $this->hist[] = ['role' => 'user', 'content' => $userMessage];
   
           /* ------ 1st 呼び出し ------ */
           $messages = [['role' => 'system', 'content' => $this->buildSystemPrompt()]];
           foreach (array_slice($this->hist, -20) as $m) $messages[] = $m;
   
           $first = json_decode($this->callOpenAI($messages, $this->toolDefinition()), true);
           if (!isset($first['choices'][0]['message'])) {
               return ['message' => 'APIエラー', 'via_tool' => false, 'error' => $first];
           }
   
           /* ------ ループ（最大 3 ターン） ------ */
           $botText     = '';
           $toolContent = '{}';
           $staffFlg    = false;
           $unknownFlg  = false;
           $loop        = 0;
   
           while ($loop < 3) {
               $msg = $first['choices'][0]['message'];
   
               /* content が来たら完了 */
               if (!isset($msg['function_call'])) {
                   $botText = $msg['content'] ?? '';
                   break;
               }
   
               /* ---------- tool 実行 ---------- */
               $fn   = $msg['function_call']['name'] ?? '';
               $args = json_decode($msg['function_call']['arguments'] ?? '{}', true);
               $incrementLoop = true;   // saveUnknown だけ false にする
   
               switch ($fn) {
                   case 'getInfo':
                       $toolContent = $this->handleToolCall(strtolower($args['slot'] ?? ''));
                       break;
   
                   case 'searchFAQ':
                   case 'getFAQ':
                       $faq = $this->searchFAQ($args['keywords'] ?? '') ?: ['answer' => 'NOT_FOUND'];
                       $toolContent = json_encode($faq, JSON_UNESCAPED_UNICODE);
                       break;
   
                   case 'updateCtx':
                       $_SESSION["ctx_{$this->pageUid}_{$this->userId}"] =
                           array_merge($_SESSION["ctx_{$this->pageUid}_{$this->userId}"] ?? [], $args);
                       $toolContent = '{"status":"ok"}';
                       break;
   
                   case 'saveUnknown':
                       save_unknown(
                           $this->db(), $this->pageUid, $this->userId,
                           $args['question'] ?? $userMessage,
                           $args['tag']      ?? ''
                       );
                       $unknownFlg     = true;
                       $toolContent    = '{"status":"logged"}';
                       $incrementLoop  = false;              /* ★ ノーカウント */
                       break;
   
                   case 'notifyStaff':
                       save_staff($this->db(), [
                           'page_uid'   => $this->pageUid,
                           'user_id'    => $this->userId,
                           'task'       => $args['task'],
                           'detail'     => $args['detail'],
                           'room_name'  => $args['room_name'] ?? '',
                           'urgency'    => $args['urgency']    ?? 'mid',
                           'importance' => $args['importance'] ?? 'mid'
                       ]);
                       $staffFlg   = true;
                       $toolContent = '{"status":"ok"}';
                       break;
   
                   default:
                       $toolContent = '{"error":"unknown_function"}';
               }
   
               /* assistant(function_call) → function(result) を履歴へ */
               $this->hist[] = ['role' => 'assistant', 'function_call' => $msg['function_call']];
               $this->hist[] = ['role' => 'function',  'name' => $fn, 'content' => $toolContent];
   
               if ($incrementLoop) $loop++;                 /* saveUnknown はカウントしない */
   
               /* 次ターンへ */
               if ($loop >= 3) break;                       /* 上限に達したら抜ける */
   
               $messages = array_merge($messages, array_slice($this->hist, -2));
               $first    = json_decode($this->callOpenAI($messages, $this->toolDefinition()), true);
           }
   
           /* ------ 最終応答を履歴へ ------ */
           if ($botText === '') $botText = '確認しますのでお待ちください。';
           $this->hist[] = ['role' => 'assistant', 'content' => $botText];
           $this->saveHistory();
   
           return [
               'message'      => $botText,
               'via_tool'     => true,
               'get_json'     => json_decode($toolContent, true),
               'unknown'      => $unknownFlg,
               'staff_called' => $staffFlg
           ];
       }
       /* ==============================================================
         slot → DB 取り出し
       ============================================================== */
    private function handleToolCall(string $slot): string
    {
        if (isset($this->extraCache[$slot])) {
            return json_encode($this->extraCache[$slot], JSON_UNESCAPED_UNICODE);
        }

        if ($slot === 'nearby_stores') {
            $st = $this->db()->prepare('SELECT * FROM stores WHERE facility_uid=?');
            $st->execute([$this->pageUid]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: ['error'=>'NOT_FOUND'];
            return json_encode($this->extraCache[$slot]=$rows, JSON_UNESCAPED_UNICODE);
        }

        $map = [
            'location'=>['facility_ai_data','contact_data,geo_data,location_notes'],
            'stay'    =>['facility_ai_data','stay_data'],
            'rule'    =>['facility_ai_data','rule_data,rule_notes'],
            'amenity' =>['facility_ai_data','amenities_data,amenities_notes'],
            'service' =>['facility_ai_data','services_data'],
            'contact' =>['facility_ai_data','contact_data']
        ];
        if (!isset($map[$slot])) return '{"error":"invalid slot"}';

        [$tbl,$cols] = $map[$slot];
        $st = $this->db()->prepare("SELECT $cols FROM $tbl WHERE page_uid=? LIMIT 1");
        $st->execute([$this->pageUid]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['error'=>'NOT_FOUND'];

        foreach ($row as $k=>$v) if (is_string($v)&&$this->isJson($v)) $row[$k]=json_decode($v,true);
        return json_encode($this->extraCache[$slot]=$row, JSON_UNESCAPED_UNICODE);
    }

    /* ------------ FAQ 検索 ------------ */
    private function searchFAQ(string $kw): ?array
    {
        $kw = trim($kw); if ($kw==='') return null;
        $sql = 'SELECT id,question,answer,tags,hits FROM question
                WHERE page_uid=:uid AND state<>"archive"
                  AND LENGTH(TRIM(answer))>0
                  AND MATCH(question,tags) AGAINST(:nat IN NATURAL LANGUAGE MODE)
                ORDER BY hits DESC, pinned DESC, updated_at DESC LIMIT 5';
        $st = $this->db()->prepare($sql);
        $st->execute([':uid'=>$this->pageUid, ':nat'=>$kw]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        if ($rows) {
            // すべての取得したFAQのhitsをインクリメント
            $ids = array_column($rows, 'id');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $this->db()->prepare("UPDATE question SET hits=hits+1 WHERE id IN ($placeholders)")
                      ->execute($ids);
        }
        return $rows ?: null;
    }

    /* ------------ OpenAI ------------ */
    /* ------------ OpenAI 呼び出し ------------ */
    private function callOpenAI(array $messages, array $tools): string
    {
        $body = [
            'model'       => 'gpt-4o',
            'messages'    => $messages,
            'temperature' => 0.7
        ];
        if ($tools) {
            $body['functions']     = array_column($tools, 'function');
            $body['function_call'] = 'auto';
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $info     = curl_getinfo($ch);      // ★ ここで $info を作る
        curl_close($ch);

        /* ---- デバッグログ ---- */
        if ($err) {
            error_log("CURL_ERR: ".$err);
            return json_encode(['error'=>'CURL error: '.$err]);
        }
        if ($info['http_code'] !== 200) {
            error_log("HTTP=".$info['http_code']." BODY=".$response);
            return json_encode(['error'=>'API error: '.$response]);
        }
        if (!$response) {
            error_log("EMPTY response");
            return json_encode(['error'=>'Empty response']);
        }
        /* --------------------- */

        return $response;
    }


    /* ------------ tools 定義 ------------ */
    private function toolDefinition(): array
    {
        return [
            [ 'type'=>'function','function'=>[
                'name'=>'getInfo',
                'description'=>'施設 DB から追加情報を取得',
                'parameters'=>[
                    'type'=>'object',
                    'properties'=>[
                        'slot'=>['type'=>'string','enum'=>[
                            'location','stay','rule',
                            'amenity','service','contact','nearby_stores'
                        ]]
                    ],
                    'required'=>['slot']
                ]
            ]],
            [ 'type'=>'function','function'=>[
                'name'=>'searchFAQ',
                'description'=>'FAQ から関連 Q&A を取得',
                'parameters'=>[
                    'type'=>'object',
                    'properties'=>['keywords'=>['type'=>'string']],
                    'required'=>['keywords']
                ]
            ]],
            [ 'type'=>'function','function'=>[
                'name'=>'saveUnknown',
                'description'=>'未知の質問を保存',
                'parameters'=>[
                    'type'=>'object',
                    'properties'=>[
                        'question'=>['type'=>'string'],
                        'tag'     =>['type'=>'string']
                    ],
                    'required'=>['question','tag']
                ]
            ]],
            [ 'type'=>'function','function'=>[
                'name'=>'notifyStaff',
                'description'=>'スタッフ依頼を送信',
                'parameters'=>[
                    'type'=>'object',
                    'properties'=>[
                        'task'      =>['type'=>'string'],
                        'detail'    =>['type'=>'string'],
                        'room_name' =>['type'=>'string'],
                        'urgency'   =>['type'=>'string','enum'=>['low','mid','high']],
                        'importance'=>['type'=>'string','enum'=>['low','mid','high']]
                    ],
                    'required'=>['task','detail','room_name']
                ]
            ]],
            [ 'type'=>'function','function'=>[
                'name'=>'updateCtx',
                'description'=>'ゲスト ctx を更新',
                'parameters'=>[
                    'type'=>'object',
                    'properties'=>[
                        'name'      =>['type'=>'string','maxLength'=>30],
                        'room_name' =>['type'=>'string','maxLength'=>60],
                        'stage'     =>['type'=>'string']
                    ]
                ]
            ]]
        ];
    }

    /* ------------ 共通 util ------------ */
    private function db(): PDO
    {
        return $this->db ??= require dirname(dirname(__DIR__)).'/public/core/db.php';
    }

    private function isJson(string $s): bool
    {
        json_decode($s);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
