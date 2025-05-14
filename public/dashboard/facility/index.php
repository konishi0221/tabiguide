<?php

require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(dirname(__DIR__)) . '/core/template_builder.php';

$page_uid = $_GET['page_uid'] ?? '';
if (!$page_uid) {
    echo "page_uid is required";
    exit;
}

$page_uid_safe = rawurlencode($page_uid);   // for URLs

// ゲストページ URL 判定
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = preg_match('/^(localhost|127\.0\.0\.1)/', $host);
$guest_base_url = $isLocal
    ? 'http://localhost:5173/guest/'   // 開発環境 (Vite)
    : 'https://app.tabiguide.net/guest/'; // 本番

$guest_url = $guest_base_url . $page_uid_safe;

// 施設概要取得
$fac = $pdo->prepare('SELECT base_data, amenities_data, rule_data, location_data, services_data, contact_data, stay_data, geo_data
                      FROM facility_ai_data WHERE page_uid = ? LIMIT 1');
$fac->execute([$page_uid]);
$fac = $fac->fetch(PDO::FETCH_ASSOC) ?: [];
$base = json_decode($fac['base_data'] ?? '{}', true);
$facility_name = $base['施設名'] ?? '(名称未設定)';

$billStmt = $pdo->prepare('SELECT plan_id, status, next_billing_date
                           FROM billing WHERE page_uid = ? LIMIT 1');
$billStmt->execute([$page_uid]);
$billing = $billStmt->fetch(PDO::FETCH_ASSOC) ?: ['plan_id'=>null,'status'=>'inactive','next_billing_date'=>null];

$plan_label = $billing['plan_id'] ?: '未契約';

// --- API 使用金額取得 ---
$tokStmt = $pdo->prepare('SELECT gpt_price, google_price FROM token_usage WHERE page_uid = ? LIMIT 1');
$tokStmt->execute([$page_uid]);
$tok = $tokStmt->fetch(PDO::FETCH_ASSOC) ?: ['gpt_price' => 0, 'google_price' => 0];

$gpt_price    = (float)$tok['gpt_price'];
$google_price = (float)$tok['google_price'];
$api_total    = $gpt_price + $google_price;


// プランごとの月額上限（plan_limits テーブル参照）
$plan_id = $billing['plan_id'] ?? '';
$limitStmt = $pdo->prepare('SELECT api_max_price FROM plan_limits WHERE plan_id = ? LIMIT 1');
$limitStmt->execute([$plan_id]);

$api_limit = (int)$limitStmt->fetchColumn();
if ($api_limit <= 0) {
    $api_limit = 10000;   // フォールバック
}
// サブスクが inactive のときは上限 0 として表示しない
if (($billing['status'] ?? '') !== 'active') {
    $api_limit = 0;
}

$api_ratio = $api_limit ? min(100, $api_total / $api_limit * 100) : 0;
$gpt_ratio = $api_limit ? min(100, $gpt_price / $api_limit * 100) : 0;
$google_ratio = $api_limit ? min(100, $google_price / $api_limit * 100) : 0;


$cols = ['base_data','amenities_data','rule_data','location_data',
         'services_data','contact_data','stay_data','geo_data'];

// 施設タイプ取得してテンプレ読み込み
$type = $base['施設タイプ'] ?? 'minpaku';
$template = buildTemplate($type, [], false);   // 空テンプレ (array)

/**** 保存データの中に 1 項目でも実際の値があれば true ****/
function sectionFilled(array $saved): bool
{
    $stack = [$saved];
    while ($stack) {
        $cur = array_pop($stack);
        foreach ($cur as $v) {
            if (is_array($v)) {
                $stack[] = $v;            // 深掘り
            } else {
                if (!is_bool($v) && trim((string)$v) !== '') {
                    return true;          // 空でない値を見つけた
                }
                if (is_bool($v) && $v === true) {
                    return true;          // bool true も入力扱い
                }
            }
        }
    }
    return false;
}

$basic_done = true;
foreach ($cols as $c) {
    $saved = json_decode($fac[$c] ?? '{}', true);
    if (!sectionFilled($saved)) {   // 1項目も入っていなければ未完了
        $basic_done = false;
        break;
    }
}
$basic_cls = $basic_done ? 'done' : '';
error_log(sprintf('[debug] basic_done=%d page_uid=%s', $basic_done, $page_uid));

// 部屋データ取得
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE page_uid = ? ORDER BY id ASC");
$stmt->execute([$page_uid]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 近隣のお店登録数チェック
$cntStmt = $pdo->prepare('SELECT COUNT(*) FROM stores WHERE facility_uid = ?');
$cntStmt->execute([$page_uid]);
$store_count = (int)$cntStmt->fetchColumn();

if ($store_count >= 5) {
    $store_state = 'done';      // 完了
    $store_icon  = '&#10003;';  // check
} elseif ($store_count > 0) {
    $store_state = 'partial';   // 途中
    $store_icon  = '&bull;';    // dot (色で区別)
} else {
    $store_state = '';          // 未開始
    $store_icon  = '&bull;';
}

// デザイン作成チェック
$desStmt = $pdo->prepare('SELECT COUNT(*) FROM design WHERE page_uid = ?');
$desStmt->execute([$page_uid]);
$has_design  = (int)$desStmt->fetchColumn() > 0;

$design_state = $has_design ? 'done' : '';
$design_icon  = $has_design ? '&#10003;' : '&bull;';

// チャット設定チェック
$chatStmt = $pdo->prepare('SELECT voice_first_message, chat_first_message, chat_charactor 
                           FROM design WHERE page_uid = ? LIMIT 1');
$chatStmt->execute([$page_uid]);
$chatRow = $chatStmt->fetch(PDO::FETCH_ASSOC);

$chat_done = false;
if ($chatRow) {
    $chat_done = trim(($chatRow['voice_first_message']   ?? '')) !== ''
              && trim(($chatRow['chat_first_message']    ?? '')) !== ''
              && trim(($chatRow['chat_charactor']        ?? '')) !== '';
}

$chat_state = $chat_done ? 'done' : '';
$chat_icon  = $chat_done ? '&#10003;' : '&bull;';

$billing_done  = ($billing['status'] === 'active');
$billing_state = $billing_done ? 'done' : '';
$billing_icon  = $billing_done ? '&#10003;' : '&bull;';

/* 全てのステップが完了か */
$all_steps_done = $basic_done
               && $store_state === 'done'
               && $design_state === 'done'
               && $chat_state  === 'done'
               && $billing_state === 'done';

global $facility_type;

$roomLabel = getRoomLabel($facility_type);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($roomLabel) ?>一覧</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <link rel="stylesheet" href="/assets/css/facility.css">
  <style>
    .progress {display:flex;height:12px;background:#eee;border-radius:6px;overflow:hidden}
    .progress .seg-gpt    {background:#4fa3ff;}
    .progress .seg-google {background:#f9bb2d;}
  </style>
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app">
  <main class="grid">

  <!-- 概要 -->
  <section class="card" id="overview">
    <h2>施設概要</h2>
    <p class="name">
      施設名：<?= htmlspecialchars($facility_name) ?>
      <a class="link-btn" href="<?= htmlspecialchars($guest_url, ENT_QUOTES) ?>" target="_blank">公開ページ</a>
    </p>
    <p>プラン：<?= htmlspecialchars($plan_label) ?></p>
<?php if (!$all_steps_done): ?>
    <ul class="steps">
      <li class="<?= $basic_cls ?>">
        <a href="/dashboard/ai/base.php?page_uid=<?= htmlspecialchars($page_uid) ?>">
          <span class="dot"><?= $basic_done ? '&#10003;' : '&bull;' ?></span><label>基本情報</label>
        </a>
      </li>
      <li class="<?= $store_state ?>">
        <a href="/dashboard/stores/list.php?page_uid=<?= htmlspecialchars($page_uid) ?>">
          <span class="dot"><?= $store_icon ?></span><label>近隣のお店登録</label>
        </a>
      </li>
      <li class="<?= $design_state ?>">
        <a href="/dashboard/design/design.php?page_uid=<?= htmlspecialchars($page_uid) ?>">
          <span class="dot"><?= $design_icon ?></span><label>デザイン作成</label>
        </a>
      </li>
      <li class="<?= $chat_state ?>">
        <a href="/dashboard/design/chat.php?page_uid=<?= htmlspecialchars($page_uid) ?>">
          <span class="dot"><?= $chat_icon ?></span><label>チャット設定</label>
        </a>
      </li>
      <li class="<?= $billing_state ?>">
        <a href="/dashboard/billing/index.php?page_uid=<?= htmlspecialchars($page_uid) ?>">
          <span class="dot"><?= $billing_icon ?></span><label>決済設定</label>
        </a>
      </li>
    </ul>
<?php endif; ?>
  </section>

  <!-- API 使用量 -->
  <section class="card" id="api-usage">
    <h2>API 使用量 (今月)</h2>
    <p>
      GPT: ¥<?= number_format($gpt_price, 2) ?> /
      Google: ¥<?= number_format($google_price, 2) ?> <br>
      <strong>合計: ¥<?= number_format($api_total, 2) ?> /
<?php if($api_limit): ?>¥<?= number_format($api_limit) ?><?php else: ?>—<?php endif; ?></strong>
    </p>
    <div class="progress">
      <div class="seg-gpt"    style="width:<?= round($gpt_ratio,1) ?>%"></div>
      <div class="seg-google" style="width:<?= round($google_ratio,1) ?>%"></div>
    </div>
  </section>

  <!-- 決済状況 -->
  <section class="card" id="billing">
    <h2>決済状況</h2>
    <p>プラン：<span class="kpi"><?= htmlspecialchars($plan_label) ?></span></p>
    <p>ステータス：<span class="kpi"><?= htmlspecialchars(ucfirst($billing['status'] ?? '未契約')) ?></span></p>
    <p>次回請求日：<span class="kpi"><?= htmlspecialchars($billing['next_billing_date'] ?: '－') ?></span></p>
    <a class="link-btn" href="/dashboard/billing/index.php?page_uid=<?= htmlspecialchars($page_uid) ?>">請求詳細</a>
  </section>

</main>
</div>

</div>
</body>
</html>
