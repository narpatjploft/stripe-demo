<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $body            = json_decode(file_get_contents('php://input'), true);
    $customerId      = $body['customer_id']       ?? null;
    $priceId         = $body['price_id']          ?? null;
    $paymentMethodId = $body['payment_method_id'] ?? null;

    if (!$customerId)      throw new Exception('customer_id is required.');
    if (!$priceId)         throw new Exception('price_id is required.');
    if (!$paymentMethodId) throw new Exception('payment_method_id is required.');

    // Set as default payment method for invoices
    \Stripe\Customer::update($customerId, [
        'invoice_settings' => [
            'default_payment_method' => $paymentMethodId,
        ],
    ]);

    $subscription = \Stripe\Subscription::create([
        'customer'               => $customerId,
        'items'                  => [['price' => $priceId]],
        'default_payment_method' => $paymentMethodId,
        'expand'                 => ['latest_invoice.payment_intent'],
    ]);

    echo json_encode([
        'success'      => true,
        'subscription' => [
            'id'     => $subscription->id,
            'status' => $subscription->status,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
