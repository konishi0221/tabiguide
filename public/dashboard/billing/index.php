<?php

require_once dirname(__DIR__) . '/../core/dashboard_head.php';
require_once dirname(dirname(__DIR__)) . '/core/template_builder.php';
$pdo = require dirname(dirname(__DIR__)) . '/core/db.php';

// 対象施設 UID
$page_uid = $_GET['page_uid'] ?? '';
$page_uid_safe = htmlspecialchars($page_uid, ENT_QUOTES);

// 課金情報を取得
$stmt = $pdo->prepare('SELECT plan_id, status, next_billing_date
                       FROM billing WHERE page_uid = ? LIMIT 1');
$stmt->execute([$page_uid]);
$billing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'plan_id' => null,          // 未契約
    'status'  => 'inactive',
    'next_billing_date' => null
];
// dd($billing);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>決済設定</title>
  <link rel="stylesheet" href="/assets/css/admin_layout.css">
  <link rel="stylesheet" href="/assets/css/admin_design.css">
  <script src="https://js.stripe.com/v3"></script>
</head>
<body>
<?php include(dirname(__DIR__) . '/components/dashboard_header.php'); ?>
<div class="dashboard-container">
<?php include(dirname(__DIR__) . '/components/side_navi.php'); ?>

<div id="app">
<main class="grid">
  <section class="card">
    <h2>ご契約状況</h2>

    <div class="plan-grid" style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap">
      <?php
        $plans = [
          'lite'       => ['name'=>'ライト',     'price'=>'¥1,000'],
          'basic'      => ['name'=>'ベーシック', 'price'=>'¥3,000'],
          'pro'        => ['name'=>'プロ',       'price'=>'¥5,000'],
          'business'   => ['name'=>'ビジネス',   'price'=>'¥10,000'],
          'enterprise' => ['name'=>'エンタープライズ','price'=>'¥30,000'],
        ];
        $currentPlan = $billing['plan_id'];
        foreach ($plans as $id=>$p):
          $isCurrent = ($id === $currentPlan);
          $isSubscribed = ($billing['status'] === 'active');
      ?>
        <div class="plan-card" style="border:<?= ($isCurrent && $isSubscribed) ? '2px solid #2ecc71' : '1px solid #ccc' ?>;background:<?= ($isCurrent && $isSubscribed) ? '#e9ffe9' : '#fff' ?>;padding:1rem;width:160px;text-align:center">
          <h3 style="margin:0 0 .5rem"><?= $p['name'] ?></h3>
          <p style="font-size:1.2rem;margin:.5rem 0"><?= $p['price'] ?>/月</p>

          <?php
            if ($isCurrent && $isSubscribed):
          ?>
            <span style="color:#2ecc71;font-weight:bold;font-size:1.1rem">ご契約中</span>
          <?php else: ?>
            <button class="select-plan link-btn"
                    data-plan="<?= $id ?>"
                    style="margin-top:.5rem;width:100%">このプランを選択</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($billing['status'] === 'active') : ?>
      <button id="portalBtn" class="link-btn" style="margin-top:1rem">カード情報を更新</button>
    <?php endif; ?>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const stripe = Stripe('<?= getenv("STRIPE_OPEN_KEY") ?>');
  const uid = '<?= $page_uid_safe ?>';
  const subsActive = '<?= $billing['status'] === 'active' ? '1' : '0' ?>' === '1';

  
  /* ---- plan-card click -> checkout ---- */
  const selectable = document.querySelectorAll('.select-plan');
  if (selectable.length) selectable.forEach(btn=>{
    btn.addEventListener('click', async e=>{
      const planId = e.currentTarget.dataset.plan;
      if (!planId) return; // safety
      let api = '/api/billing/create_checkout.php';
      const body = new URLSearchParams({ page_uid: uid, plan_id: planId });

      console.log(uid, planId)


      if (subsActive) {
        // 既存サブスクあり→Portalで変更
        api = '/api/billing/create_portal.php';
      }
      const res = await fetch(api, { method:'POST', body });

    if (!res.ok) {
      console.log(res)
    }

      let json={};
      try { json = await res.clone().json(); } catch { alert('JSONエラー'); return; }

      if (json.id) await stripe.redirectToCheckout({ sessionId: json.id });
      else if (json.url) location.href = json.url;
    });
  });

  const portalBtn = document.getElementById('portalBtn');
  if (portalBtn) {
    portalBtn.addEventListener('click', async () => {
      const body = new URLSearchParams({ page_uid: uid });
      const res  = await fetch('/api/billing/create_portal.php', { method:'POST', body });
      if (!res.ok) {
        const text = await res.text();
        console.error('portal api error:', text);
        alert('ポータル生成に失敗しました');
        return;
      }
      const {url} = await res.json();
      location.href = url;
    });
  }
});
</script>
</div>

</div>
</body>
</html>
