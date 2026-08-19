<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $limit          = min((int)($_GET['limit'] ?? 10), 100);
    $startingAfter  = $_GET['starting_after'] ?? null;
    $endingBefore   = $_GET['ending_before']  ?? null;

    $params = [
        'limit' => $limit,
    ];
    if ($startingAfter) $params['starting_after'] = $startingAfter;
    if ($endingBefore)  $params['ending_before']  = $endingBefore;

    $customers = \Stripe\Customer::all($params);

    $list = [];
    foreach ($customers->data as $c) {
        $list[] = [
            'id'       => $c->id,
            'name'     => $c->name     ?? '',
            'email'    => $c->email    ?? '',
            'phone'    => $c->phone    ?? '',
            'created'  => $c->created,
            'currency' => $c->currency ?? '',
        ];
    }

    echo json_encode([
        'customers' => $list,
        'has_more'  => $customers->has_more,
        'first_id'  => $list[0]['id']                   ?? null,
        'last_id'   => $list[count($list) - 1]['id']    ?? null,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
