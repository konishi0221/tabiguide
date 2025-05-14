<?php
declare(strict_types=1);
// error_log('stripe_webhook');
/**
 * Stripe Webhook エンドポイント
 * - checkout.session.completed : 初回課金成功
 * - customer.subscription.updated : プラン変更
 * - customer.subscription.deleted : 解約
 *
 * DB 更新はすべてここで行う。
 */
require_once dirname(__DIR__,3) . '/vendor/autoload.php';
require_once dirname(__DIR__,3) . '/public/core/config.php';
$pdo = require dirname(__DIR__,3) . '/public/core/db.php';

// ----- Read Stripe secret key -----
$secretKey = getenv('STRIPE_SECRET_KEY') ?: getenv('STRIPE_SECRET') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? '');
// error_log('[stripe_webhook] secretKey='.substr($secretKey ?: 'null', 0, 12));
if ($secretKey) {
    \Stripe\Stripe::setApiKey($secretKey);
} else {
    error_log('[stripe_webhook] NO SECRET KEY FOUND');
}

$payload = file_get_contents('php://input');
// error_log('[stripe_webhook] raw='.substr($payload,0,500));   // 最初の500文字だけ
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret  = getenv('STRIPE_WEBHOOK_SECRET') ?: ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
// error_log('[stripe_webhook] sigSecret='.substr($secret ?: 'null', 0, 12));

header('Content-Type: application/json; charset=utf-8');

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch (\Throwable $e) {
    error_log('[stripe_webhook] '.$e->getMessage());
    http_response_code(400);
    echo json_encode(['error'=>'sig']);
    exit;
}

// error_log('[stripe_webhook] event='.$event->type);
if (isset($event->id)) {
    error_log('[stripe_webhook] id='.$event->id);
}

/* ---- ハンドラ ---- */
switch ($event->type) {
    /* 初回 Checkout 完了 */
    case 'checkout.session.completed':
        $s = $event->data->object;
        updateBilling($pdo, $s->customer, $s->subscription, 'active');

        /* ----- reset monthly token counters ----- */
        try {
            $cust    = \Stripe\Customer::retrieve($s->customer);
            $pageUid = $cust->metadata->page_uid ?? null;

            if ($pageUid) {
                /* token_usage: reset monthly cost counter */
                $pdo->prepare(
                    'INSERT INTO token_usage (page_uid, gpt_price, google_price, updated_at)
                     VALUES (?, 0, 0, NOW())
                     ON DUPLICATE KEY UPDATE
                       gpt_price    = 0,
                       google_price = 0,
                       updated_at   = NOW()'
                )->execute([$pageUid]);
                error_log("[stripe_webhook] token_usage cost reset uid={$pageUid}");
            } else {
                error_log('[stripe_webhook] token_usage reset skipped: no page_uid');
            }
        } catch (\Throwable $e) {
            error_log('[stripe_webhook] token_usage reset error '.$e->getMessage());
        }
        break;

    /* プラン・数量変更 */
    case 'customer.subscription.updated':
        $sub = $event->data->object;
        // error_log('update');
        $status = $sub->status; // active|past_due|canceled|unpaid...
        updateBilling($pdo, $sub->customer, $sub->id, $status);
        break;

    /* 解約 → status を canceled に更新 */
    case 'customer.subscription.deleted':
        $sub = $event->data->object;
        updateBilling($pdo, $sub->customer, $sub->id, 'canceled');
        break;

    default:
        // ignore others
}

http_response_code(200);
echo json_encode(['status'=>'ok']);

/* ---- 関数 ---- */
function updateBilling(PDO $pdo, string $customerId, ?string $subId, string $status): void
{
    error_log("[stripe_webhook] updateBilling start cust={$customerId} sub={$subId} status={$status}");
    // ---- fetch plan_id & next_billing_date ----
    $planId = null;
    $next   = null;

    if ($subId) {
        try {
            $sub = \Stripe\Subscription::retrieve($subId, ['expand'=>['items.data.price']]);
            $price = $sub->items->data[0]->price ?? null;

            /* price_id → plan_id Fallback table */
            $priceToPlan = [
                'price_1RMNniP1TiwWGBdxvnDMJoi9' => 'lite',
                'price_1RMNn8P1TiwWGBdxvY4PT5hE' => 'basic',
                'price_1RMNxoP1TiwWGBdxZmK3RSTB' => 'pro',
                'price_1RMNy1P1TiwWGBdxbe1koYMb' => 'business',
                'price_1RMNyHP1TiwWGBdxfFIgffD8' => 'enterprise',
            ];

            if ($price) {
                error_log('[stripe_webhook] price.id='.$price->id.
                          ' lookup_key='.($price->lookup_key ?? 'null').
                          ' nickname='.($price->nickname ?? 'null').
                          ' metadata_plan='.(isset($price->metadata->plan_id) ? $price->metadata->plan_id : 'null'));

                // ① lookup_key 推奨
                $planId = $price->lookup_key ?: null;

                // ② metadata.plan_id fallback
                if (!$planId && isset($price->metadata->plan_id) && $price->metadata->plan_id !== '') {
                    $planId = $price->metadata->plan_id;
                }

                // ③ nickname fallback
                if (!$planId && $price->nickname) {
                    $planId = $price->nickname;
                }

                // ④ mapping table fallback
                if (!$planId && isset($priceToPlan[$price->id])) {
                    $planId = $priceToPlan[$price->id];
                }

                // ⑤ 最後の砦として price ID
                if (!$planId) {
                    $planId = $price->id;
                }
                error_log('[stripe_webhook] resolved plan_id='.$planId);
            }

            $next = date('Y-m-d', $sub->current_period_end);
        } catch (\Throwable $e) {
            error_log('[stripe_webhook] sub fetch '.$e->getMessage());
        }
    }

    // ---- build query ----
    $sql = 'UPDATE billing
               SET status = :st,
                   next_billing_date = :dt,
                   updated_at = NOW()';
    $params = [':st'=>$status, ':dt'=>$next];

    if ($planId) {
        $sql .= ', plan_id = :pl';
        $params[':pl'] = $planId;
    }
    if ($subId) {
        $sql .= ', stripe_subscription = :sub';
        $params[':sub'] = $subId;
    } else {
        // keep existing subscription id
        $subIdFromDb = $pdo->prepare('SELECT stripe_subscription FROM billing WHERE stripe_customer=? LIMIT 1');
        $subIdFromDb->execute([$customerId]);
        $existing = $subIdFromDb->fetchColumn();
        if ($existing) {
            $sql .= ', stripe_subscription = :sub';
            $params[':sub'] = $existing;
        }
    }
    $sql .= ' WHERE stripe_customer = :cust';
    $params[':cust'] = $customerId;

    error_log("[stripe_webhook] about to update plan={$planId} next={$next}");
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    error_log('[stripe_webhook] row updated='.$stmt->rowCount());

    /* --- if no rows updated, insert new billing row --- */
    if ($stmt->rowCount() === 0) {
        try {
            $cust = \Stripe\Customer::retrieve($customerId);
            $pageUid = $cust->metadata->page_uid ?? null;
            error_log('[stripe_webhook] fallback uid=' . ($pageUid ?: 'NULL') .
                      ' plan=' . ($planId ?: 'NULL') .
                      ' status=' . $status);

            if ($pageUid) {
                $ins = $pdo->prepare(
                    'INSERT INTO billing
                         (page_uid, stripe_customer, stripe_subscription, plan_id, status, next_billing_date, created_at, updated_at)
                     VALUES
                         (:uid, :cust, :sub, :pl, :st, :dt, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                         stripe_customer      = VALUES(stripe_customer),
                         stripe_subscription  = VALUES(stripe_subscription),
                         plan_id              = VALUES(plan_id),
                         status               = VALUES(status),
                         next_billing_date    = VALUES(next_billing_date),
                         updated_at           = NOW()'
                );
                $ins->execute([
                    ':uid'  => $pageUid,
                    ':cust' => $customerId,
                    ':sub'  => $subId,
                    ':pl'   => $planId,
                    ':st'   => $status,
                    ':dt'   => $next
                ]);
                $rows = $ins->rowCount();
                $err  = $ins->errorInfo();
                error_log('[stripe_webhook] row inserted=' . $rows .
                          ' sqlstate=' . ($err[0] ?? '') .
                          ' msg=' . ($err[2] ?? ''));
            } else {
                error_log('[stripe_webhook] insert skipped: page_uid metadata missing');
            }
        } catch (\Throwable $e) {
            error_log('[stripe_webhook] insert error '.$e->getMessage());
        }
    }

    // log for debugging
    error_log("[stripe_webhook] billing updated cust={$customerId} plan={$planId} status={$status}");
}
