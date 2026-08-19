<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $body     = json_decode(file_get_contents('php://input'), true);
    $name     = trim($body['name']     ?? '');
    $amount   = (int)($body['amount']  ?? 0); // in cents
    $interval = $body['interval']      ?? 'month';
    $currency = strtolower($body['currency'] ?? 'usd');

    if (!$name)         throw new Exception('Plan name is required.');
    if ($amount <= 0)   throw new Exception('Amount must be greater than 0.');

    $validIntervals = ['day', 'week', 'month', 'year'];
    if (!in_array($interval, $validIntervals)) {
        throw new Exception('Invalid interval. Must be: day, week, month, or year.');
    }

    $product = \Stripe\Product::create(['name' => $name]);

    $price = \Stripe\Price::create([
        'product'     => $product->id,
        'unit_amount' => $amount,
        'currency'    => $currency,
        'recurring'   => ['interval' => $interval],
    ]);

    echo json_encode([
        'success' => true,
        'plan'    => [
            'id'       => $price->id,
            'name'     => $name,
            'amount'   => $amount,
            'currency' => strtoupper($currency),
            'interval' => $interval,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
