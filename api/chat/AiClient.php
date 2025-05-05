<?php
/* =======================================================================
   AiClient.php ― OpenAI chat wrapper
   ======================================================================= */
declare(strict_types=1);


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
            'model'       => 'gpt-4o',
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
        return json_decode($res, true);
    }

    public function embeddings(string $model, string $input): array
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
        return json_decode($res, true);
    }

}
