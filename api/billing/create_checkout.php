<?php
/**
 * Stripe Checkout セッション生成
 * POST: page_uid, price_id (省略可)
 * 返値: { "url": "https://checkout.stripe.com/..." }
 */
require_once __DIR__ . '/../../vendor/autoload.php';   // stripe-php
require_once __DIR__ . '/../cros.php';
require_once dirname(__DIR__,2) . '/public/core/config.php';

header('Content-Type: application/json; charset=utf-8');

$pageUid = $_POST['page_uid'] ?? '';
$planId  = $_POST['plan_id'] ?? 'lite';   // ドロップダウンの value
/* plan_id → Stripe Price ID */
$planToPrice = [
  // Stripe Price IDs (例: price_1SabcDEFghIJklMN)
  'lite'       => 'price_1RMNniP1TiwWGBdxvnDMJoi9',
  'basic'      => 'price_1RMNn8P1TiwWGBdxvY4PT5hE',
  'pro'        => 'price_1RMNxoP1TiwWGBdxZmK3RSTB',
  'business'   => 'price_1RMNy1P1TiwWGBdxbe1koYMb',
  'enterprise' => 'price_1RMNyHP1TiwWGBdxfFIgffD8',
];
if (!isset($planToPrice[$planId])) {
  http_response_code(400);
  echo json_encode(['error'=>'invalid plan_id']);
  exit;
}
$priceId = $planToPrice[$planId];

if ($pageUid === '') {
  http_response_code(400);
  echo json_encode(['error'=>'page_uid 必須']);
  exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
  /* Customer lookup by page_uid metadata → create if none */
  $customerSearch = \Stripe\Customer::search([
    'query' => 'metadata["page_uid"]:"'.$pageUid.'"',
    'limit' => 1,
  ]);
  if (count($customerSearch->data) > 0) {
      $customerId = $customerSearch->data[0]->id;
  } else {
      $customer   = \Stripe\Customer::create(['metadata'=>['page_uid'=>$pageUid]]);
      $customerId = $customer->id;
  }

  /* Checkout Session */
  $scheme  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
  $host    = $_SERVER['HTTP_HOST'];
  // After payment, return directly to billing complete page
  $success = $scheme.$host."/dashboard/billing/complete.php?session_id={CHECKOUT_SESSION_ID}&page_uid={$pageUid}";
  $cancel  = $scheme.$host."/dashboard/billing/index.php?page_uid={$pageUid}";

  $session = \Stripe\Checkout\Session::create([
    'mode'        => 'subscription',
    'customer'    => $customerId,
    'success_url' => $success,
    'cancel_url'  => $cancel,
    'subscription_data' => [
        'metadata' => ['plan_id' => $planId]
    ],
    'line_items'  => [[ 'price'=>$priceId, 'quantity'=>1 ]]
  ]);

  echo json_encode(['id'=>$session->id, 'url'=>$session->url]);

} catch (Throwable $e) {
  http_response_code(500);
  error_log('[stripe_checkout] '.$e->getMessage());
  echo json_encode(['error'=>'Stripe API error']);
}