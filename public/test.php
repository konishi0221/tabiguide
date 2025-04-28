
ini_set('display_errors', 0);
set_error_handler(fn($no, $msg, $file, $line) => renderErrorPage($msg, $file, $line));
set_exception_handler(fn($e) => renderErrorPage($e->getMessage(), $e->getFile(), $e->getLine()));

function renderErrorPage(string $msg, string $file, int $line): void {
    http_response_code(500);
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>エラー</title>
  <style>
    html, body { margin:0; padding:0; width:100%; height:100%; }
    body { display:flex; align-items:center; justify-content:center;
      background:#2b2b2b; color:#f1f1f1; font-family:sans-serif; }
    .box { text-align:center; max-width:90%; }
    h1 { margin-bottom:0.5em; font-size:2em; }
    p, small { margin:0.2em 0; }
    small { opacity:0.7; }
  </style>
</head>
<body>
  <div class="box">
    <h1>予期せぬエラーが発生しました</h1>
    <p>メッセージ: {$msg}</p>
    <small>場所: {$file} (行 {$line})</small>
  </div>
</body>
</html>
HTML;
    exit;
}

// テスト用
// trigger_error("テストエラー", E_USER_ERROR);
// throw new Exception("テスト例外");
