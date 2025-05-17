<?php
/**
 * Lightweight PHPMailer wrapper for Tabiguide
 *
 * Usage:
 *   Mailer::send('dest@example.com', '件名', '<p>本文</p>');
 *
 * Env vars required:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, FROM_MAIL, FROM_NAME
 *   (set in .env / Cloud Run env vars)
 *
 * Run once for a quick test:
 *   php public/core/mail/mailer.php dest@example.com
 */
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static function build(): PHPMailer
    {
        $m = new PHPMailer(true);

        // SMTP basic
        $m->isSMTP();
        $m->Host       = getenv('SMTP_HOST') ?: 'localhost';
        $m->Port       = getenv('SMTP_PORT') ?: 587;
        $m->SMTPAuth   = true;
        $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // auth
        $m->Username   = getenv('SMTP_USER');
        $m->Password   = getenv('SMTP_PASS');

        // sender
        $m->setFrom(
            getenv('FROM_MAIL') ?: 'no-reply@example.com',
            getenv('FROM_NAME') ?: 'Tabiguide'
        );

        // misc
        $m->CharSet = 'UTF-8';

        return $m;
    }

    /**
     * Send simple HTML mail.
     */
    public static function send(string $to, string $subject, string $html, array $attach = []): bool
    {
        try {
            $m = self::build();
            $m->addAddress($to);
            $m->Subject = $subject;
            $m->isHTML(true);
            $m->Body = $html;
            // テキスト版を自動生成（HTMLタグ除去＋改行整形）
            $m->AltBody = strip_tags(
                str_replace(
                    ['<br>','<br/>','<br />','</p>','</div>','</li>'],
                    ["\n","\n","\n","\n","\n","\n"],
                    $html
                )
            );

            foreach ($attach as $path) {
                $m->addAttachment($path);
            }

            return $m->send();
        } catch (Exception $e) {
            error_log('[Mailer] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send using HTML template.
     * $messageHtml はテンプレート内で埋め込まれるプレーン本文 (HTML可)。
     * $subject 省略時は 'Tabiguide Notification'。
     */
    public static function sendWithTemplate(
        string $to,
        string $messageHtml,
        string $subject = 'Tabiguide Notification',
        array $attach = []
    ): bool {
        // テンプレートが本文を参照できるように変数を用意
        $now = date('Y-m-d H:i:s');
        $subject_for_tpl = $subject;
        $subject = $subject_for_tpl; // template uses $subject
        // make $messageHtml and $now available
        $html = include __DIR__ . '/html_templete.php';

        return self::send($to, $subject_for_tpl, $html, $attach);
    }
}

/* ----- CLI quick test ---------------------------------------------------- */
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $dest = $argv[1] ?? null;
    if (!$dest) {
        fwrite(STDERR, "Usage: php mailer.php dest@example.com\n");
        exit(1);
    }
    echo Mailer::send($dest, 'Mailer test', '<p>It works!</p>') ? "OK\n" : "FAIL\n";
}

