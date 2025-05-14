<?php
/* =======================================================================
   AiClient.php ― OpenAI chat wrapper
   ======================================================================= */
declare(strict_types=1);

require_once dirname(__DIR__,2).'/public/core/token_usage.php';


class AiClient
{
    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey
            ?: (defined('OPENAI_API_KEY')
                ? OPENAI_API_KEY
                : (getenv('OPENAI_API_KEY') ?: '')
            );
    }

    public function chat(array $messages, array $tools = [], array $opts = []): array
    {
        $body = [
            'model'       => 'gpt-4o-mini',
            'messages'    => $messages,
            'temperature' => 0.7
        ];
        if ($tools) {
            $body['tools'] = $tools;            // これだけで十分
            if (isset($opts['tool_choice'])) {  // 強制したい時だけ上書き
                $body['tool_choice'] = $opts['tool_choice'];
            }
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT_MS     => 15000
        ]);

        // error_log('[OpenAI body] '.json_encode($body, JSON_UNESCAPED_UNICODE));

        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($err)          return ['error'=>'CURL '.$err];
        if ($code !== 200) return ['error'=>'HTTP '.$code, 'body'=>$res ?: ''];
        if (!$res)         return ['error'=>'EMPTY'];

        $json = json_decode($res, true);

        /* ---- cost accounting for chat ---- */
        if (!empty($opts['uid']) && function_exists('chargeGPT')) {
            $u = $json['usage'] ?? [];
            $inTok  = (int)($u['prompt_tokens']     ?? $u['total_tokens'] ?? 0);
            $outTok = (int)($u['completion_tokens'] ?? 0);
            $modelUsed = $body['model'] ?? 'gpt-4o';

            chargeGPT($opts['uid'], $modelUsed, $inTok, $outTok);
        }
        /* ---------------------------------- */

        return $json;
    }

    public function embeddings(string $model, string $input, array $opts = []): array
    {
        $body = [
            'model' => $model,
            'input' => $input
        ];
    
        $ch = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);
    
        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    
        if ($err)  return ['error'=>$err];
        if ($code!==200) return ['error'=>"HTTP $code",'body'=>$res ?: ''];

        $json = json_decode($res, true);

        /* ---- cost accounting for embeddings ---- */
        if (!empty($opts['uid']) && function_exists('chargeEmbedding')) {
            if (isset($json['usage']['total_tokens'])) {
                $tok = (int)$json['usage']['total_tokens'];
            } elseif (isset($json['usage']['prompt_tokens'])) {
                $tok = (int)$json['usage']['prompt_tokens'];         // fallback older field
            } else {
                $tok = 0;
                error_log("[EMB cost] NO usage field uid={$opts['uid']} model={$model}");
            }

            if ($tok > 0) {
                chargeEmbedding($opts['uid'], $model, $tok);
            }
        }
        /* ---------------------------------------- */

        return $json;
    }

}
