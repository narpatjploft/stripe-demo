<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

function mapCharge($c): array
{
    $customer = is_object($c->customer) ? $c->customer : null;

    return [
        'id'             => $c->id,
        'customer_id'    => $customer->id ?? (is_string($c->customer) ? $c->customer : ''),
        'customer_name'  => $customer->name ?? '',
        'customer_email' => $customer->email ?? '',
        'amount'         => $c->amount,
        'currency'       => strtoupper($c->currency ?? ''),
        'status'         => $c->status,
        'description'    => $c->description ?? '',
        'type'           => $c->invoice ? 'Subscription' : 'One-time',
        'invoice_id'     => is_string($c->invoice) ? $c->invoice : '',
        'refunded'       => (bool) $c->refunded,
        'created'        => $c->created,
    ];
}

function fetchChargesForCustomer(string $customerId, string $status, array $expand): array
{
    if ($status !== '') {
        $safeStatus = str_replace("'", "\\'", $status);
        $result     = \Stripe\Charge::search([
            'query'  => "customer:'{$customerId}' AND status:'{$safeStatus}'",
            'limit'  => 100,
            'expand' => $expand,
        ]);

        return $result->data;
    }

    $result = \Stripe\Charge::all([
        'customer' => $customerId,
        'limit'    => 100,
        'expand'   => $expand,
    ]);

    return $result->data;
}

try {
    $limit         = min((int)($_GET['limit'] ?? 10), 100);
    $startingAfter = $_GET['starting_after'] ?? null;
    $endingBefore  = $_GET['ending_before']  ?? null;
    $email         = trim($_GET['email'] ?? '');
    $status        = trim($_GET['status'] ?? '');

    $expand = ['data.customer'];

    if ($email !== '') {
        $safeEmail = str_replace("'", "\\'", $email);
        $customers = \Stripe\Customer::search([
            'query' => "email~'{$safeEmail}'",
            'limit' => 100,
        ]);

        $charges = [];
        foreach ($customers->data as $c) {
            foreach (fetchChargesForCustomer($c->id, $status, $expand) as $charge) {
                $charges[] = $charge;
            }
        }

        $list = array_map('mapCharge', $charges);
        usort($list, fn($a, $b) => $b['created'] - $a['created']);

        echo json_encode([
            'transactions' => $list,
            'has_more'     => false,
            'first_id'     => $list[0]['id'] ?? null,
            'last_id'      => $list[count($list) - 1]['id'] ?? null,
            'filtered'     => true,
        ]);
        exit;
    }

    if ($status !== '') {
        $safeStatus = str_replace("'", "\\'", $status);
        $result     = \Stripe\Charge::search([
            'query'  => "status:'{$safeStatus}'",
            'limit'  => 100,
            'expand' => $expand,
        ]);

        $list = [];
        foreach ($result->data as $c) {
            $list[] = mapCharge($c);
        }
        usort($list, fn($a, $b) => $b['created'] - $a['created']);

        echo json_encode([
            'transactions' => $list,
            'has_more'     => false,
            'first_id'     => $list[0]['id'] ?? null,
            'last_id'      => $list[count($list) - 1]['id'] ?? null,
            'filtered'     => true,
        ]);
        exit;
    }

    $params = [
        'limit'  => $limit,
        'expand' => $expand,
    ];
    if ($startingAfter) $params['starting_after'] = $startingAfter;
    if ($endingBefore)  $params['ending_before']  = $endingBefore;

    $charges = \Stripe\Charge::all($params);

    $list = [];
    foreach ($charges->data as $c) {
        $list[] = mapCharge($c);
    }

    echo json_encode([
        'transactions' => $list,
        'has_more'     => $charges->has_more,
        'first_id'     => $list[0]['id'] ?? null,
        'last_id'      => $list[count($list) - 1]['id'] ?? null,
        'filtered'     => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
