<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

function planName($price, array &$productCache): string
{
    if (!$price) {
        return '';
    }
    if (!empty($price->nickname)) {
        return $price->nickname;
    }

    $productId = is_object($price->product) ? $price->product->id : ($price->product ?? '');
    if (!$productId) {
        return '';
    }

    if (!isset($productCache[$productId])) {
        $product = \Stripe\Product::retrieve($productId);
        $productCache[$productId] = $product->name ?? $productId;
    }

    return $productCache[$productId];
}

function mapSubscription($s, array &$productCache): array
{
    $customer = is_object($s->customer) ? $s->customer : null;
    $item     = $s->items->data[0] ?? null;
    $price    = $item->price ?? null;

    return [
        'id'                 => $s->id,
        'status'             => $s->status,
        'customer_id'        => $customer->id ?? (is_string($s->customer) ? $s->customer : ''),
        'customer_name'      => $customer->name ?? '',
        'customer_email'     => $customer->email ?? '',
        'plan_name'          => planName($price, $productCache),
        'amount'             => $price->unit_amount ?? 0,
        'currency'           => strtoupper($price->currency ?? ''),
        'interval'           => $price->recurring->interval ?? '',
        'current_period_end' => $s->current_period_end,
        'created'            => $s->created,
    ];
}

try {
    $limit         = min((int)($_GET['limit'] ?? 10), 100);
    $startingAfter = $_GET['starting_after'] ?? null;
    $endingBefore  = $_GET['ending_before']  ?? null;
    $email         = trim($_GET['email'] ?? '');
    $status        = trim($_GET['status'] ?? '');

    $expand       = ['data.customer', 'data.items.data.price'];
    $productCache = [];

    if ($email !== '') {
        $safeEmail = str_replace("'", "\\'", $email);
        $customers = \Stripe\Customer::search([
            'query' => "email~'{$safeEmail}'",
            'limit' => 100,
        ]);

        $list = [];
        foreach ($customers->data as $c) {
            $subParams = [
                'customer' => $c->id,
                'limit'    => 100,
                'expand'   => $expand,
            ];
            if ($status) {
                $subParams['status'] = $status;
            }

            $subs = \Stripe\Subscription::all($subParams);
            foreach ($subs->data as $s) {
                $list[] = mapSubscription($s, $productCache);
            }
        }

        usort($list, fn($a, $b) => $b['created'] - $a['created']);

        echo json_encode([
            'subscriptions' => $list,
            'has_more'      => false,
            'first_id'      => $list[0]['id'] ?? null,
            'last_id'       => $list[count($list) - 1]['id'] ?? null,
            'filtered'      => true,
        ]);
        exit;
    }

    $params = [
        'limit'  => $limit,
        'expand' => $expand,
    ];
    if ($startingAfter) $params['starting_after'] = $startingAfter;
    if ($endingBefore)  $params['ending_before']  = $endingBefore;
    if ($status)        $params['status']        = $status;

    $subs = \Stripe\Subscription::all($params);

    $list = [];
    foreach ($subs->data as $s) {
        $list[] = mapSubscription($s, $productCache);
    }

    echo json_encode([
        'subscriptions' => $list,
        'has_more'      => $subs->has_more,
        'first_id'      => $list[0]['id'] ?? null,
        'last_id'       => $list[count($list) - 1]['id'] ?? null,
        'filtered'      => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
