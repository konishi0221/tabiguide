<?php
/**
 * Very light wrapper for Google Cloud Text‑to‑Speech REST API.
 * Only requires an API key (no OAuth). Returns audioContent (base64 MP3).
 *
 * Usage:
 *   $tts = new TtsClient($_ENV['GOOGLE_TTS_KEY']);
 *   $b64 = $tts->synthesize('こんにちは', 'ja-JP');
 *   // audio/mp3 data:  base64_decode($b64)
 */
class TtsClient
{
    /** 円 / 1文字（$16 / 1Mchars @ ¥155/US$） */
    private const YEN_PER_CHAR = 0.00248;

    private string $apiKey;
    private string $endpoint = 'https://texttospeech.googleapis.com/v1/text:synthesize';

    public function __construct(string $apiKey)
    {
        $this->apiKey = trim($apiKey);
        if ($this->apiKey === '') {
            throw new RuntimeException('Google TTS API key is missing.');
        }
    }

    /**
     * @param string $text      plain text to speak
     * @param string $lang      BCP‑47 locale (ja-JP, en-US …)
     * @param string $voiceName (optional) full voice name
     * @return string  audioContent (base64 MP3)
     * @throws RuntimeException on API error
     */
    public function synthesize(string $text, string $lang = 'ja-JP', string $voiceName = ''): string
    {
        $text = mb_substr($text, 0, 4000);  // Google limit
        if ($text === '') {
            return '';
        }

        $payload = [
            'input' => ['text' => $text],
            'voice' => [
                'languageCode' => $lang,
                'name'         => $voiceName ?: $this->defaultVoice($lang)
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'speakingRate'  => 1.1
            ]
        ];

        $ch = curl_init($this->endpoint . '?key=' . urlencode($this->apiKey));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            throw new RuntimeException("TTS request failed: {$err}");
        }
        $j = json_decode($res, true);
        if (isset($j['error'])) {
            throw new RuntimeException('TTS error: ' . ($j['error']['message'] ?? ''));
        }

        // Optionally attach cost in response header for debugging
        // header('X-TTS-Cost-Yen: '.self::estimateCost($text));

        return $j['audioContent'] ?? '';
    }

    /**
     * Pick a WaveNet voice name from language code.
     * Falls back to Standard‑A if not in map.
     */
    private function defaultVoice(string $lang): string
    {
        static $voiceMap = [
            'ja'  => 'ja-JP-Wavenet-A',
            'en'  => 'en-US-Wavenet-A',
            'ko'  => 'ko-KR-Wavenet-A',
            'zh'  => 'cmn-CN-Wavenet-A',
            'zht' => 'cmn-TW-Wavenet-A',
            'th'  => 'th-TH-Wavenet-A',
            'vi'  => 'vi-VN-Wavenet-A',
            'id'  => 'id-ID-Wavenet-A',
            'es'  => 'es-ES-Wavenet-D'
        ];
        return $voiceMap[$lang] ?? ($lang . '-Standard-A');
    }

    /**
     * Rough cost estimate in Japanese Yen for given text length.
     * @param string $text
     * @return float yen
     */
    public static function estimateCost(string $text): float
    {
        return round(mb_strlen($text) * self::YEN_PER_CHAR, 6);
    }
}