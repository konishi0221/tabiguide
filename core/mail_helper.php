<?php
function sendVerificationMail($to, $token) {
    $verifyUrl = "https://yourdomain.com/login/verify/index.php?token=" . urlencode($token);
    $subject = "【Tabiguide】メールアドレス確認のお願い";
    $message = <<<EOT
{$to} 様

以下のリンクをクリックして、メールアドレスの確認を完了してください。

{$verifyUrl}

※このリンクは一定時間で無効になります。

---
Tabiguide 運営
EOT;

    // ローカル環境ではログ出力のみ（本番用は下記に記述）
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) mkdir($logDir, 0777, true);
        file_put_contents("{$logDir}/mail_debug.log", "To: {$to}\nSubject: {$subject}\n\n{$message}\n\n", FILE_APPEND);
        return true;
    }

    // 本番用に切り替えるときはここ
    // return mail($to, $subject, $message);

    return true; // 仮で常に成功扱い
}
