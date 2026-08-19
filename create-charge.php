<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $body            = json_decode(file_get_contents('php://input'), true);
    $customerId      = $body['customer_id']       ?? null;
    $paymentMethodId = $body['payment_method_id'] ?? null;
    $amount          = (int)($body['amount']      ?? 0); // in cents
    $currency        = strtolower($body['currency'] ?? 'usd');
    $description     = trim($body['description']  ?? '');

    if (!$customerId)      throw new Exception('customer_id is required.');
    if (!$paymentMethodId) throw new Exception('payment_method_id is required.');
    if ($amount <= 0)      throw new Exception('Amount must be greater than 0.');

    $paymentIntent = \Stripe\PaymentIntent::create([
        'customer'       => $customerId,
        'payment_method' => $paymentMethodId,
        'amount'         => $amount,
        'currency'       => $currency,
        'description'    => $description ?: null,
        'confirm'        => true,
        'off_session'    => true,
    ]);

    echo json_encode([
        'success' => true,
        'payment' => [
            'id'     => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => $amount,
        ],
    ]);
} catch (\Stripe\Exception\CardException $e) {
    http_response_code(402);
    echo json_encode(['error' => $e->getError()->message]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
