<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $customerId = trim($_GET['customer_id'] ?? '');
    if (!$customerId) {
        throw new Exception('customer_id is required.');
    }

    // Fetch customer to get default payment method
    $customer = \Stripe\Customer::retrieve($customerId);
    $defaultPmId = $customer->invoice_settings->default_payment_method ?? null;

    $paymentMethods = \Stripe\PaymentMethod::all([
        'customer' => $customerId,
        'type'     => 'card',
    ]);

    $cards = [];
    foreach ($paymentMethods->data as $pm) {
        $cards[] = [
            'id'        => $pm->id,
            'brand'     => ucfirst($pm->card->brand),
            'last4'     => $pm->card->last4,
            'exp_month' => str_pad($pm->card->exp_month, 2, '0', STR_PAD_LEFT),
            'exp_year'  => $pm->card->exp_year,
            'is_default'=> ($pm->id === $defaultPmId),
        ];
    }

    echo json_encode(['cards' => $cards]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
