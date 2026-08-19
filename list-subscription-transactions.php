<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $subscriptionId = trim($_GET['subscription_id'] ?? '');
    if (!$subscriptionId) {
        throw new Exception('subscription_id is required.');
    }

    $invoices = \Stripe\Invoice::all([
        'subscription' => $subscriptionId,
        'limit'        => 100,
        'expand'       => ['data.charge'],
    ]);

    $transactions = [];
    foreach ($invoices->data as $inv) {
        $charge = is_object($inv->charge) ? $inv->charge : null;

        $transactions[] = [
            'id'            => $inv->id,
            'invoice_number'=> $inv->number ?? '',
            'amount'        => $inv->amount_paid ?: $inv->amount_due,
            'currency'      => strtoupper($inv->currency ?? ''),
            'status'        => $inv->status,
            'charge_id'     => $charge->id ?? (is_string($inv->charge) ? $inv->charge : ''),
            'charge_status' => $charge->status ?? '',
            'created'       => $inv->created,
            'period_start'  => $inv->period_start,
            'period_end'    => $inv->period_end,
        ];
    }

    usort($transactions, fn($a, $b) => $b['created'] - $a['created']);

    echo json_encode(['transactions' => $transactions]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
