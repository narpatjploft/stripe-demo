<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

function mapInvoice($inv): array
{
    $customer = is_object($inv->customer) ? $inv->customer : null;

    return [
        'id'              => $inv->id,
        'number'          => $inv->number ?? '',
        'customer_id'     => $customer->id ?? (is_string($inv->customer) ? $inv->customer : ''),
        'customer_name'   => $customer->name ?? '',
        'customer_email'  => $customer->email ?? '',
        'amount'          => $inv->amount_paid ?: $inv->amount_due,
        'currency'        => strtoupper($inv->currency ?? ''),
        'status'          => $inv->status,
        'subscription_id' => is_string($inv->subscription) ? $inv->subscription : ($inv->subscription->id ?? ''),
        'created'         => $inv->created,
        'period_start'    => $inv->period_start,
        'period_end'      => $inv->period_end,
    ];
}

function fetchInvoicesForCustomer(string $customerId, string $status, array $expand): array
{
    $params = [
        'customer' => $customerId,
        'limit'    => 100,
        'expand'   => $expand,
    ];
    if ($status !== '') {
        $params['status'] = $status;
    }

    $result = \Stripe\Invoice::all($params);

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

        $invoices = [];
        foreach ($customers->data as $c) {
            foreach (fetchInvoicesForCustomer($c->id, $status, $expand) as $invoice) {
                $invoices[] = $invoice;
            }
        }

        $list = array_map('mapInvoice', $invoices);
        usort($list, fn($a, $b) => $b['created'] - $a['created']);

        echo json_encode([
            'invoices' => $list,
            'has_more'   => false,
            'first_id'   => $list[0]['id'] ?? null,
            'last_id'    => $list[count($list) - 1]['id'] ?? null,
            'filtered'   => true,
        ]);
        exit;
    }

    if ($status !== '') {
        $invoices = \Stripe\Invoice::all([
            'status' => $status,
            'limit'  => 100,
            'expand' => $expand,
        ]);

        $list = [];
        foreach ($invoices->data as $inv) {
            $list[] = mapInvoice($inv);
        }
        usort($list, fn($a, $b) => $b['created'] - $a['created']);

        echo json_encode([
            'invoices' => $list,
            'has_more'   => false,
            'first_id'   => $list[0]['id'] ?? null,
            'last_id'    => $list[count($list) - 1]['id'] ?? null,
            'filtered'   => true,
        ]);
        exit;
    }

    $params = [
        'limit'  => $limit,
        'expand' => $expand,
    ];
    if ($startingAfter) $params['starting_after'] = $startingAfter;
    if ($endingBefore)  $params['ending_before']  = $endingBefore;

    $invoices = \Stripe\Invoice::all($params);

    $list = [];
    foreach ($invoices->data as $inv) {
        $list[] = mapInvoice($inv);
    }

    echo json_encode([
        'invoices' => $list,
        'has_more'   => $invoices->has_more,
        'first_id'   => $list[0]['id'] ?? null,
        'last_id'    => $list[count($list) - 1]['id'] ?? null,
        'filtered'   => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
