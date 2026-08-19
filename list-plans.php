<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $prices = \Stripe\Price::all([
        'limit'  => 100,
        'active' => true,
        'expand' => ['data.product'],
        'type'   => 'recurring',
    ]);

    $plans = [];
    foreach ($prices->data as $price) {
        $productName = is_object($price->product) ? $price->product->name : $price->product;
        $plans[] = [
            'id'       => $price->id,
            'name'     => $productName,
            'amount'   => $price->unit_amount,
            'currency' => strtoupper($price->currency),
            'interval' => $price->recurring->interval,
        ];
    }

    echo json_encode(['plans' => $plans]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
