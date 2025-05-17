<?php
/**
 * メール共通 HTML テンプレート
 * 使い方:
 *   $html = include __DIR__.'/html_templete.php';
 *   (変数 $subject, $now を事前に定義しておく)
 */

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject, ENT_QUOTES) ?></title>
</head>
<body style="margin:0;padding:30px;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;line-height:1.6;">
  <!-- wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" align="center" style="background:#ffffff;border-radius:6px;overflow:hidden;min-height:600px;">
    <!-- header -->
    <tr style="height:60px;border-bottom:1px #dcdcdc;">
      <td style="background:white;text-align:center;padding:5px; border-bottom:1px #dcdcdc;">
        <a href="https://tabiguide.net/dashboard/" style="text-decoration:none;">
          <img src="https://tabiguide.net/assets/images/cms_logo.png" alt="Tabiguide" style="height:40px;border:0;display:block;margin:0 auto;">
        </a>
      </td>
    </tr>

    <!-- content -->
    <tr>
      <td style="padding:24px;font-size:15px;color:#333;min-height:600px;vertical-align:top;border-top:solid 1px #dcdcdc;border-bottom:solid 1px #dcdcdc;">
        <p><?= $messageHtml ?? '' ?></p>
        <p style="margin:0;">送信時刻: <?= htmlspecialchars($now, ENT_QUOTES) ?></p>
      </td>
    </tr>

    <!-- footer -->
    <tr>
      <td style="background:#fafafa;color:#777;font-size:12px;padding:5px 24px;text-align:center;height:40px;border-top:1px #dcdcdc;">
        &copy; 2025 Tabiguide
      </td>
    </tr>
  </table>
</body>
</html>
<?php
return ob_get_clean();
