<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

function stripeCount(string $class, array $params = [], int $max = 500): array
{
    $total   = 0;
    $hasMore = false;
    $params['limit'] = min(100, $max);

    while ($total < $max) {
        $list = $class::all($params);
        $total += count($list->data);

        if (!$list->has_more || !$list->data) {
            $hasMore = (bool) $list->has_more;
            break;
        }

        $params['starting_after'] = $list->data[count($list->data) - 1]->id;
        $hasMore = true;
    }

    if ($total >= $max && ($list->has_more ?? false)) {
        $hasMore = true;
    }

    return ['count' => $total, 'has_more' => $hasMore];
}

function sumSucceededCharges(int $max = 500): array
{
    $total    = 0;
    $count    = 0;
    $hasMore  = false;
    $currency = 'USD';
    $params   = ['limit' => min(100, $max)];

    while ($count < $max) {
        $charges = \Stripe\Charge::all($params);

        foreach ($charges->data as $charge) {
            if ($charge->status === 'succeeded' && !$charge->refunded) {
                $total += $charge->amount;
                if ($total === $charge->amount) {
                    $currency = strtoupper($charge->currency ?? 'USD');
                }
            }
        }

        $count += count($charges->data);

        if (!$charges->has_more || !$charges->data) {
            $hasMore = (bool) $charges->has_more;
            break;
        }

        $params['starting_after'] = $charges->data[count($charges->data) - 1]->id;
        $hasMore = true;
    }

    if ($count >= $max && ($charges->has_more ?? false)) {
        $hasMore = true;
    }

    return ['amount' => $total, 'currency' => $currency, 'has_more' => $hasMore];
}

function mapCharge($c): array
{
    $customer = is_object($c->customer) ? $c->customer : null;

    return [
        'id'             => $c->id,
        'customer_name'  => $customer->name ?? '',
        'customer_email' => $customer->email ?? '',
        'amount'         => $c->amount,
        'currency'       => strtoupper($c->currency ?? ''),
        'status'         => $c->status,
        'created'        => $c->created,
    ];
}

function mapSubscription($s): array
{
    $customer = is_object($s->customer) ? $s->customer : null;
    $item     = $s->items->data[0] ?? null;
    $price    = $item->price ?? null;

    return [
        'id'             => $s->id,
        'status'         => $s->status,
        'customer_name'  => $customer->name ?? '',
        'customer_email' => $customer->email ?? '',
        'amount'         => $price->unit_amount ?? 0,
        'currency'       => strtoupper($price->currency ?? ''),
        'interval'       => $price->recurring->interval ?? '',
        'created'        => $s->created,
    ];
}

try {
    $customers     = stripeCount(\Stripe\Customer::class);
    $subscriptions = stripeCount(\Stripe\Subscription::class, ['status' => 'active']);
    $plans         = stripeCount(\Stripe\Price::class, ['type' => 'recurring', 'active' => true]);
    $revenue       = sumSucceededCharges();

    $recentCharges = \Stripe\Charge::all([
        'limit'  => 8,
        'expand' => ['data.customer'],
    ]);

    $recentSubs = \Stripe\Subscription::all([
        'limit'  => 5,
        'expand' => ['data.customer', 'data.items.data.price'],
    ]);

    $charges = [];
    foreach ($recentCharges->data as $c) {
        $charges[] = mapCharge($c);
    }

    $subs = [];
    foreach ($recentSubs->data as $s) {
        $subs[] = mapSubscription($s);
    }

    echo json_encode([
        'stats' => [
            'customers'     => $customers,
            'subscriptions' => $subscriptions,
            'plans'         => $plans,
            'revenue'       => $revenue,
        ],
        'recent_charges'       => $charges,
        'recent_subscriptions' => $subs,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
