<?php
// include_onece('/../core/config.php');
require_once __DIR__ . '/../core/config.php';

function callChatGPT($messages) {
    $apiKey = "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";
    $url = "https://api.openai.com/v1/chat/completions";

    $data = [
        "model" => "gpt-4o",
        "messages" => $messages,
        "temperature" => 0.7
    ];

    $options = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\nAuthorization: Bearer " . $apiKey,
            "content" => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    return json_decode($response, true);
}
?>
