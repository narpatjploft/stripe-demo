<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $body = json_decode(file_get_contents('php://input'), true);
    $paymentMethodId = $body['payment_method_id'] ?? null;

    if (!$paymentMethodId) {
        throw new Exception('payment_method_id is required.');
    }

    $pm = \Stripe\PaymentMethod::retrieve($paymentMethodId);
    $pm->detach();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
