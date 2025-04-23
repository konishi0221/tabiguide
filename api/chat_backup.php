<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  include_once(  dirname(__DIR__) . '/core/config.php');

  // セッションを開始
  session_start();

  // ユーザーからのメッセージを取得
  $data = json_decode(file_get_contents('php://input'), true);
  $userMessage = $data['message'] ?? '';
  $page_uid = $data['pageUid'] ?? ''; // 必要に応じて POSTやGETに変更OK
  $url = 'https://api.openai.com/v1/chat/completions';


  error_log("API CALL - page_uid: " . $page_uid);
  error_log("API CALL - userMessage: " . $userMessage);


  require_once dirname(__DIR__) . '/core/db.php';

  // return var_dump($data);

  if (!$page_uid) {
      die('Error: page_uid が指定されていません。');
  }

  // DBからpromptを取得
  $stmt = $pdo->prepare("SELECT prompt FROM facility_ai_data WHERE page_uid = ? LIMIT 1");
  $stmt->execute([$page_uid]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $prompt = $row['prompt'] ?? '';

  if (empty($prompt)) {
      die('Error: プロンプトが取得できませんでした。');
  }

  // 会話履歴の初期化
  if (!isset($_SESSION['conversation_history'])) {
      $_SESSION['conversation_history'] = [];
  }

  // 会話履歴にユーザーのメッセージを追加
  $userMessageData = ['role' => 'user', 'content' => $userMessage];
  $_SESSION['conversation_history'][] = $userMessageData;

  // OpenAI APIに送るメッセージ構成
  $messages = [
      ['role' => 'system', 'content' => $prompt]
  ];


  // 過去の会話を追加
  foreach ($_SESSION['conversation_history'] as $message) {
      $messages[] = $message;
  }
    // トークン数が500以上になった場合は会話履歴を削減
    if (count($messages) > 10) {
        array_splice($_SESSION['conversation_history'], 0, 1);
    }

    // リクエストボディの準備
    $data = [
      'model' => 'gpt-4o',
        // 'model' => 'gpt-3.5-turbo',
        'messages' => $messages
    ];

    // リクエストのヘッダー
    $headers = [
        'Cache-Control: no-cache, no-store, must-revalidate',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_key
    ];

    // cURLセッションを初期化
    $ch = curl_init($url);

    // cURLのオプション設定
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // APIへのリクエストを実行
    $response = curl_exec($ch);
    if ($response === false) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    curl_close($ch);

    // APIからのレスポンスをデコード
    $responseData = json_decode($response, true);

    // echo json_encode(['message' => $botMessage]);

    // botからのメッセージを取得
    $botMessage = $responseData['choices'][0]['message']['content'] ?? '申し訳ありませんが、回答が見つかりませんでした。:api';

    // 会話履歴にボットの応答を追加
    $botMessageData = ['role' => 'assistant', 'content' => $botMessage];
    $_SESSION['conversation_history'][] = $botMessageData;


    // レスポンスを返す
    echo json_encode(['message' => $botMessage]);
}
?>
