<?php
/**
 * core/regist_mail.php
 * メール関連の共通ヘルパー
 */

require_once __DIR__ . '/mailer.php';

/**
 * アカウント確認メール送信
 *
 * @param string $email  宛先メールアドレス
 * @param string $token  email_verification_token (DB で生成済み)
 * @return bool          成功したら true
 */
function sendVerificationMail(string $email, string $token): bool
{
    // 確認リンク
    $link = 'https://tabiguide.net/login/verify/index.php?token=' . urlencode($token);

    // メール本文 (HTML)
    $messageHtml = <<<HTML
<p>Tabiguide にご登録いただきありがとうございます。</p>
<p>以下のリンクをクリックしてメールアドレスを確認してください。</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{$link}" style="display:inline-block;background:#0066ff;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none;">メールアドレスを確認</a>
</p>
<p>もしボタンがクリックできない場合は、以下の URL をブラウザに貼り付けてください。</p>
<p style="word-break:break-all;"><a href="{$link}">{$link}</a></p>
HTML;

    $subject = '【Tabiguide】メールアドレス確認のお願い';

    // 送信
    return Mailer::sendWithTemplate($email, $messageHtml, $subject);
}
