<?php
class PromptBuilder
{
    private array  $base;        // facility_ai_data.base_data
    private string $char;        // design.chat_charactor
    private string $commonPath;  // 共通プロンプト
    private string $chatHeader;  // チャット用ヘッダー
    private string $callHeader;  // 電話用ヘッダー

    /** 言語コードを日本語名にマッピング */
    private function langName(string $code): string
    {
        return match($code) {
            'en'  => '英語',
            'ko'  => '韓国語',
            'zh'  => '中国語',
            'zht' => '繁体字中国語',
            'th'  => 'タイ語',
            'vi'  => 'ベトナム語',
            'id'  => 'インドネシア語',
            'es'  => 'スペイン語',
            default => '日本語',
        };
    }

    public function __construct(array $base, string $char, string $promptDir)
    {
        $this->base       = $base;
        $this->char       = $char;
        $dir = rtrim($promptDir, '/');
        $this->commonPath = "$dir/chat_system.txt";
        $this->chatHeader = "$dir/chat_header.txt";
        $this->callHeader = "$dir/call_header.txt";
    }

    public function build(array $ctx, string $mode): string
    {
        $headerFile = $mode === 'voice'
            ? $this->callHeader
            : $this->chatHeader;
        $header = is_file($headerFile) ? file_get_contents($headerFile) : '';

        $common = is_file($this->commonPath)
            ? file_get_contents($this->commonPath)
            : '';

        $tmpl = trim($header) . "\n\n" . trim($common)
              . "\n\n<!--基本情報-->\n" . json_encode($this->base, JSON_UNESCAPED_UNICODE)
              . "\n\n<!--ゲストの情報-->\n" . json_encode($ctx,  JSON_UNESCAPED_UNICODE)
              . "\n\n<!--あなたのキャラクター-->\n" . $this->char;
        // // 言語指定があれば指示を追加
        if (!empty($ctx['lang']) && $ctx['lang'] !== 'ja') {
            $tmpl .= "\n\n" .
                     $this->langName($ctx['lang']) .
                     'で答えてください。';
        }

        return $tmpl;
    }
}
