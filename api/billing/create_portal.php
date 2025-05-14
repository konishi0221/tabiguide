<?php
// POST: page_uid
require_once dirname(__DIR__,2) . '/vendor/autoload.php';
require_once dirname(__DIR__,2) . '/public/core/config.php';
$pdo = require dirname(__DIR__,2) . '/public/core/db.php';

header('Content-Type: application/json; charset=utf-8');

$pageUid = $_POST['page_uid'] ?? '';
$planId = $_POST['plan_id'] ?? null;
if ($pageUid==='') { http_response_code(400); echo '{"error":"page_uid"}'; exit; }

$row = $pdo->prepare('SELECT stripe_customer, stripe_subscription FROM billing WHERE page_uid=? LIMIT 1');
$row->execute([$pageUid]);
$billingRow = $row->fetch(PDO::FETCH_ASSOC);
$customerId = $billingRow['stripe_customer'] ?? null;
$subId      = $billingRow['stripe_subscription'] ?? null;

$itemId = null;
if ($subId) {
  try {
    $subObj = \Stripe\Subscription::retrieve($subId, ['expand' => ['items']]);
    $itemId = $subObj->items->data[0]->id ?? null;
  } catch (\Throwable $e) {
    error_log('[stripe_portal] sub retrieve '.$e->getMessage());
  }
}

if (!$customerId) { http_response_code(400); echo '{"error":"no customer"}'; exit; }

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https://' : 'http://';
$host   = $_SERVER['HTTP_HOST'];
$return = $scheme.$host."/dashboard/billing/index.php?page_uid={$pageUid}";

$planToPrice = [
  'lite'       => 'price_1RMNniP1TiwWGBdxvnDMJoi9',
  'basic'      => 'price_1RMNn8P1TiwWGBdxvY4PT5hE',
  'pro'        => 'price_1RMNxoP1TiwWGBdxZmK3RSTB',
  'business'   => 'price_1RMNy1P1TiwWGBdxbe1koYMb',
  'enterprise' => 'price_1RMNyHP1TiwWGBdxfFIgffD8',
];

$params = [
  'customer'   => $customerId,
  'return_url' => $return
];

if ($planId && isset($planToPrice[$planId]) && $subId && $itemId) {
  $params['flow_data'] = [
    'type' => 'subscription_update',
    'subscription_update' => [
      'subscription'        => $subId,
      'items'               => [
        [
          'id'    => $itemId,
          'price' => $planToPrice[$planId],
        ]
      ],
      'proration_behavior'  => 'always_invoice'
    ]
  ];
}

try {
  $sess = \Stripe\BillingPortal\Session::create($params);
  echo json_encode(['url'=>$sess->url]);
} catch (\Throwable $e) {
  error_log('[stripe_portal] '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>'stripe_portal']);
}