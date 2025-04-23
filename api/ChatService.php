<?php
/* =======================================================================
   ChatService.php  ― GPT‑4o function‑calling 対応版（multi‑slot 対応）
   ======================================================================= */
declare(strict_types=1);

require_once dirname(__DIR__) . '/public/core/config.php';
require_once dirname(__DIR__) . '/public/core/db.php';

session_start();

class ChatService
{
    /* ------------ プロパティ ------------ */
    private ?PDO   $db   = null;
    private array  $hist = [];          // user / assistant だけ保持
    private array  $base = [];          // facility_ai_data.base_data
    private array  $extraCache = [];    // slot 取得済みキャッシュ
    private string $pageUid;
    private string $userId;
    private string $apiKey = OPENAI_API_KEY;

    /* ------------ ctor ------------ */
    public function __construct(string $pageUid, string $userId)
    {
        $this->pageUid = $pageUid;
        $this->userId  = $userId;

        /* セッション履歴ロード (最大10件) */
        $this->hist = $_SESSION["{$pageUid}_{$userId}"] ?? [];

        /* base_data だけ先に取得 */
        $stmt = $this->db()->prepare(
            'SELECT base_data FROM facility_ai_data WHERE page_uid = ? LIMIT 1'
        );
        $stmt->execute([$pageUid]);
        $json       = $stmt->fetchColumn() ?: '{}';
        $this->base = json_decode($json, true);
    }

    /* ------------ 履歴だけ欲しい時用 ------------ */
    public function getHistory(): array { return $this->hist; }


    private function buildSystemPrompt()
    {
      $path = dirname(__DIR__).'/api/prompts/chat_system.txt';
      $tmpl = file_get_contents($path);
      if ($tmpl === false) {
          throw new \RuntimeException("prompt file missing: $path");
      }
      return $tmpl;
    }


    /* =========================================================
       ask() ― GPT‑4o + function‑calling（notifyStaff / saveUnknown）
       ========================================================= */
       public function ask(string $userMessage, array $ctx = []): array
    {
        if ($userMessage === '') {
            return ['message' => '', 'via_tool' => false];
        }

        /* ─ 1) 履歴更新（末尾 10 件） ─ */
        $this->hist[] = ['role' => 'user', 'content' => $userMessage];
        $this->hist   = array_slice($this->hist, -20);



        /* ─ 2) メッセージ組み立て ─ */
        $messages = [
          [
            'role'    => 'system',
            'content' => $this->buildSystemPrompt()
            . "\n▼パーソナリティ：{$ctx['charactor']}"
          ],
          [
            'role'    => 'system',
            'content' => '【facility_base】' .
                         json_encode($this->base, JSON_UNESCAPED_UNICODE)
          ],
          ['role'=>'system','content'=>'【guest_ctx】'.json_encode([
                'stage'        => $ctx['stage']         ?? '予約前ゲスト',
                'name'         => $ctx['name']          ?? '',
                'booking_name' => $ctx['booking_name']  ?? '',
                'room_name'    => $ctx['room_name']    ?? ''   // ★ 追加
          ], JSON_UNESCAPED_UNICODE)]


        ];
        foreach ($this->hist as $m) $messages[] = $m;

        /* ─ 3) 1st コール ─ */
        $first   = json_decode(
            $this->callOpenAI($messages, $this->toolDefinition()),
            true
        );
        $toolArr = $first['choices'][0]['message']['tool_calls'] ?? [];

        $toolJsons    = [];
        $viaTools     = [];
        $unknownFlg   = false;
        $staffFlg     = false;

        /* ─ 4) tool_calls 処理 ─ */
        if ($toolArr) {
            $messages[] = $first['choices'][0]['message'];   // assistant(tool_calls)

            foreach ($toolArr as $tc) {
                $fn   = $tc['function']['name'] ?? '';
                $args = json_decode($tc['function']['arguments'] ?? '{}', true);

                if ($fn === 'getInfo') {
                    $slot      = strtolower($args['slot'] ?? '');
                    $toolJson  = $this->handleToolCall($slot);
                    $toolJsons[$slot] = json_decode($toolJson, true);

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => $toolJson
                    ];
                }

                if ($fn === 'updateCtx') {
                      // ctx をセッションとメモリに反映
                      $_SESSION["ctx_{$this->pageUid}_{$this->userId}"] =
                          array_merge($_SESSION["ctx_{$this->pageUid}_{$this->userId}"] ?? [], $args);

                      // tool 返信
                      $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => '{"status":"ok"}'
                      ];
                  }


                if ($fn === 'saveUnknown') {
                    $unknownFlg = true;
                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => '{"status":"logged"}'
                    ];

                    save_unknown(
                        $this->db(),
                        $this->pageUid,
                        $this->userId,
                        $args['question'] ?? $userMessage,
                        $args['note']     ?? ''
                    );
                    $unknownFlg = true;
                    $botText = '確認して担当に共有しました。';


                }

                if ($fn === 'notifyStaff') {
                    // $args = {"task":"...", "detail":"...", ...}
                    save_staff($this->db(), [
                        'page_uid'   => $this->pageUid,
                        'user_id'    => $this->userId,
                        'task'       => $args['task'],
                        'detail'     => $args['detail'],
                        'room_name'  => $args['room_name'] ?? '',     // ★ room_name に統一
                        'urgency'    => $args['urgency']    ?? 'mid',
                        'importance' => $args['importance'] ?? 'mid',
                        'stage'      => $ctx['stage']       ?? '滞在中ゲスト',
                        'guest_name' => $ctx['booking_name']?? ''
                    ]);

                    $staffFlg = true;

                    $messages[] = [
                      'role'         => 'tool',
                      'tool_call_id' => $tc['id'],
                      'content'      => '{"status":"ok"}'
                    ];
                }

                $viaTools[] = $tc['function'];
            }

            /* ─ 5) 2nd コール（最終回答） ─ */
            $second = json_decode($this->callOpenAI($messages), true);
            $botText = $second['choices'][0]['message']['content'] ?? '';

            if ($botText === '') {
                $botText = $staffFlg
                    ? 'スタッフに伝えました。少々お待ちください！'
                    : '担当に確認しますのでお待ちください。';
            }
        } else {
            /* tool 不要パターン */
            $botText = $first['choices'][0]['message']['content']
                       ?? '申し訳ありません、情報を取得できませんでした。';
        }

        /* ─ 6) 履歴へ assistant 追加 ─ */
        $this->hist[] = ['role' => 'assistant', 'content' => $botText];
        $this->hist   = array_slice($this->hist, -20);
        $_SESSION["{$this->pageUid}_{$this->userId}"] = $this->hist;



        $chatId = $this->userId ?? '';   // 新規なら発番
        save_chat_log(
            $this->db(),
            $chatId,
            $this->pageUid,
            $roomId ?? null,
            $unknownFlg ? 'unknown' : ($staffFlg ? 'staff' : 'normal'),
            json_encode($this->hist, JSON_UNESCAPED_UNICODE)
        );


        /* ─ 7) フロント返却 ─ */
        return [
            'message'      => $botText,           // ← 直接テキストを入れる
            'via_tool'     => $viaTools ?: false, // json_encode を外す
            'get_json'     => $toolJsons ?: false,
            'page_uid'     => $this->pageUid,
            'unknown'      => $unknownFlg,
            'staff_called' => $staffFlg
        ];
    }

    /* ==============================================================
         slot → DB 取り出し
       ============================================================== */
    private function handleToolCall(string $slot): string
    {
        $slot = strtolower(trim($slot, "\" \t\n\r"));

        /* キャッシュ */
        if (isset($this->extraCache[$slot])) {
            return json_encode($this->extraCache[$slot], JSON_UNESCAPED_UNICODE);
        }

        $sqlMap = [
            'location' => ['facility_ai_data',
                           'contact_data, geo_data, location_notes'],
            'stay'     => ['facility_ai_data', 'stay_data'],
            'rule'     => ['facility_ai_data', 'rule_data, rule_notes'],
            'amenity'  => ['facility_ai_data', 'amenities_data, amenities_notes'],
            'service'  => ['facility_ai_data', 'services_data'],
            'contact'  => ['facility_ai_data', 'contact_data']
        ];
        if (!isset($sqlMap[$slot])) {
            return json_encode(['error'=>'invalid slot'], JSON_UNESCAPED_UNICODE);
        }
        [$tbl, $cols] = $sqlMap[$slot];

        $stmt = $this->db()->prepare("SELECT {$cols} FROM {$tbl} WHERE page_uid=? LIMIT 1");
        $stmt->execute([$this->pageUid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['error'=>'NOT_FOUND'];

        /* JSON カラムは配列へ */
        foreach ($row as $k => $v) {
            if ($this->isJson($v)) $row[$k] = json_decode($v, true);
        }

        $this->extraCache[$slot] = $row;
        return json_encode($row, JSON_UNESCAPED_UNICODE);
    }

    /* ==============================================================
         OpenAI 呼び出し
       ============================================================== */
    private function callOpenAI(array $messages, array $tools = []): string
    {
        $body = [
            'model'       => 'gpt-4o',
            'messages'    => $messages,
            'temperature' => 0.7
        ];
        if ($tools) $body['tools'] = $tools;

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res ?: '{"error":"request_failed"}';
    }

    /* ------------ tools 定義 ------------ */
    private function toolDefinition(): array
    {
        return [[
            'type'     => 'function',
            'function' => [
                'name'        => 'getInfo',
                'description' => '施設 DB から追加情報を取得',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'slot' => [
                            'type' => 'string',
                            'enum' => ['location','stay','rule',
                                       'amenity','service','contact']
                        ]
                    ],
                    'required'   => ['slot']
                ]
            ]
        ],
        [ 'type'=>'function','function'=>[
            'name'=>'saveUnknown',
            'description'=>'答えられない質問を保存',
            'parameters'=>[
              'type'=>'object',
              'properties'=>[
                'question'=>['type'=>'string'],
                'note'=>['type'=>'string']
              ],
              'required'=>['question']
            ]
        ]],
        [ 'type'=>'function','function'=>[
            'name'=>'notifyStaff',
            'description'=>'スタッフ依頼',
            'parameters'=>[
              'type'=>'object',
              'properties'=>[
                'task'      =>['type'=>'string'],
                'detail'    =>['type'=>'string'],
                'room_name' =>['type'=>'string'],                 // ★ 追加
                'urgency'   =>['type'=>'string','enum'=>['low','mid','high']],
                'importance'=>['type'=>'string','enum'=>['low','mid','high']]
              ],
              'required'=>['task','detail','room_name']           // ★ 必須に
            ]
        ]],
        [ 'type'=>'function','function'=>[
            'name'=>'updateCtx',
            'description'=>'ゲストのコンテキスト情報を更新する',
            'parameters'=>[
              'type'=>'object',
              'properties'=>[
                  'name'         => ['type'=>'string','maxLength'=>30],
                  'booking_name' => ['type'=>'string','maxLength'=>60],
                  'room_name'    => ['type'=>'string','maxLength'=>60],   // ★ 追加
                  'stage' => [ 'type'=>'string','enum'=>['予約前ゲスト', '・予約済ゲスト' ,'滞在中ゲスト','退去後ゲスト','スタッフ','開発'] ]
              ]
            ]
        ]]
      ];
    }

    /* ==============================================================
         共通 util
       ============================================================== */
    private function saveHist(string $botText): void
    {
        $this->hist[] = ['role'=>'assistant','content'=>$botText];
        $this->hist   = array_slice($this->hist, -20);
        $_SESSION["{$this->pageUid}_{$this->userId}"] = $this->hist;
    }

    private function db(): PDO
    {
        if (!$this->db) {
            // db.php は PDO を return する
            $this->db = require dirname(__DIR__) . '/core/db.php';
        }
        return $this->db;
    }

    private function isJson($s): bool
    {
        json_decode($s);
        return json_last_error() === JSON_ERROR_NONE;
    }



}
