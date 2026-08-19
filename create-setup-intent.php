<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $customerId = trim($_GET['customer_id'] ?? '');
    if (!$customerId) {
        throw new Exception('customer_id is required.');
    }

    $setupIntent = \Stripe\SetupIntent::create([
        'customer' => $customerId,
        'payment_method_types' => ['card'],
        'payment_method_options' => [
            'card' => [
                // Do NOT force 3DS.
                'request_three_d_secure' => 'automatic',
            ],
        ],
        'usage' => 'off_session',
    ]);

    echo json_encode([
        'clientSecret' => $setupIntent->client_secret,
        'publishableKey' => STRIPE_PUBLISHABLE_KEY,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}
