<?php
/**
 * test.php
 * ブラウザでアクセスすると Mailer ラッパーを使って 1 通送信するテストページ。
 * 例) http://localhost:8080/test.php?to=your@example.com
 */

declare(strict_types=1);

require_once __DIR__ . '/core/mail/mailer.php';

// 宛先 ?to=xxx があれば使用、無ければ SMTP_USER へ
$to = "konishi0221@gmail.com";
if (!$to) {
    echo 'invalid recipient';
    exit;
}

$subject      = 'Tabiguide メール送信テスト (HTML)';
$messageHtml  = 'このメールは <strong>HTML 形式</strong> のテスト送信です。';

$ok = Mailer::sendWithTemplate($to, $messageHtml, $subject);

echo $ok ? '✅ 送信成功: ' . htmlspecialchars($to, ENT_QUOTES) : '❌ 送信失敗';