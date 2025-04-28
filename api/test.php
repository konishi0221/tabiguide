<?php
require_once __DIR__.'/cros.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 受け取ったデータをログに記録
    $raw_input = file_get_contents('php://input');
    error_log("Received raw input: " . $raw_input);
    
    $data = json_decode($raw_input, true) ?? [];
    error_log("Decoded data: " . print_r($data, true));

    // テスト用の固定レスポンス
    $response = [
        'message' => 'テストメッセージです',
        'received_data' => $data,
        'via_tool' => false,
        'get_json' => null,
        'unknown' => false,
        'staff_called' => false
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error in test.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 