<?php
require_once dirname(__DIR__) . '/../core/dashboard_head.php';

// POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSONデータの取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['design']) || !isset($data['design']['page_uid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}


try {
    $design = $data['design'];
    $page_uid = $design['page_uid'];
    $voice_first = $design['voice_first_message'] ?? '';

    // 必須フィールドの検証
    $required_fields = [
        'primary_color',
        'secondary_color',
        'header_text_color',
        'tab_active_color',
        'tab_inactive_color',
        'bot_message_color',
        'user_message_color',
        'message_text_color',
        'font_family'
    ];

    foreach ($required_fields as $field) {
        if (!isset($design[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            exit;
        }
    }

    // データベースに保存 (INSERT or UPDATE)
    $design_json = json_encode($design, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO design (page_uid, design_json, voice_first_message, created_at, updated_at)
        VALUES (:page_uid, :design_json, :voice_first, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            design_json = VALUES(design_json),
            voice_first_message = VALUES(voice_first_message),
            updated_at  = NOW()
    ");

    $stmt->bindParam(':page_uid', $page_uid);
    $stmt->bindParam(':design_json', $design_json);
    $stmt->bindParam(':voice_first', $voice_first);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Design settings saved successfully'
        ]);
    } else {
        throw new Exception('Failed to save design settings');
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
} 