<?php

// モックのセッション配列
$_SESSION = [];

// テスト用のモッククラス
class MockChatService {
    private array $hist = [];
    private string $pageUid;
    private string $userId;

    public function __construct(string $pageUid, string $userId) {
        $this->pageUid = $pageUid;
        $this->userId = $userId;
        $this->hist = $_SESSION["chat_{$pageUid}_{$userId}"] ?? [];
    }

    public function ask(string $userMessage): array {
        if ($userMessage === '') {
            return ['message' => '', 'via_tool' => false];
        }

        // ユーザーメッセージを履歴に追加
        $this->addToHistory(['role' => 'user', 'content' => $userMessage]);

        // システムメッセージと履歴を含めたメッセージ配列を構築
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.']
        ];

        // 履歴を追加（最新の20件まで）
        $history = array_slice($this->hist, -20);
        foreach ($history as $m) {
            if (isset($m['role']) && isset($m['content'])) {
                $messages[] = $m;
            }
        }

        // 応答を生成（テスト用の簡単な応答）
        $response = $this->generateMockResponse($userMessage, $history);
        $this->addToHistory(['role' => 'assistant', 'content' => $response]);

        return [
            'message' => $response,
            'via_tool' => false
        ];
    }

    private function generateMockResponse(string $userMessage, array $history): string {
        if (strpos($userMessage, '名前') !== false) {
            foreach ($history as $msg) {
                if ($msg['role'] === 'user' && strpos($msg['content'], '田中') !== false) {
                    return 'はい、あなたのお名前は田中さんですね。';
                }
            }
            return '申し訳ありません。名前を覚えていません。';
        }
        return 'はい、承知しました。';
    }

    private function addToHistory(array $message): void {
        $this->hist[] = $message;
        $_SESSION["chat_{$this->pageUid}_{$this->userId}"] = array_slice($this->hist, -40);
    }

    public function getHistory(): array {
        return $this->hist;
    }
}

// テスト用の会話シーケンス
function runConversationTest() {
    $pageUid = 'test_page';
    $userId = 'test_user';
    
    try {
        $chatService = new MockChatService($pageUid, $userId);

        echo "=== 会話履歴テスト開始 ===\n\n";

        // テストケース1: 名前を覚えてもらう
        echo "テストケース1: 名前を覚えてもらう\n";
        $response1 = $chatService->ask("私の名前を覚えてください。田中です。");
        echo "Q: 私の名前を覚えてください。田中です。\n";
        echo "A: " . $response1['message'] . "\n\n";

        // セッションに保存された履歴を確認
        echo "セッション履歴1:\n";
        print_r($_SESSION["chat_{$pageUid}_{$userId}"]);
        echo "\n";

        // テストケース2: 名前を確認する
        echo "テストケース2: 名前を確認\n";
        $response2 = $chatService->ask("私の名前は何でしたか？");
        echo "Q: 私の名前は何でしたか？\n";
        echo "A: " . $response2['message'] . "\n\n";

        // セッションに保存された履歴を確認
        echo "セッション履歴2:\n";
        print_r($_SESSION["chat_{$pageUid}_{$userId}"]);
        echo "\n";

        // テストケース3: 確認の応答
        echo "テストケース3: 確認の応答\n";
        $response3 = $chatService->ask("はい、その通りです");
        echo "Q: はい、その通りです\n";
        echo "A: " . $response3['message'] . "\n\n";

        // 最終的な履歴の確認
        echo "=== 最終的な会話履歴 ===\n";
        $history = $chatService->getHistory();
        foreach ($history as $index => $message) {
            echo sprintf(
                "[%d] Role: %s, Content: %s\n",
                $index,
                $message['role'],
                $message['content']
            );
        }
    } catch (Exception $e) {
        echo "エラー: " . $e->getMessage() . "\n";
    }
}

// テストの実行
runConversationTest(); 