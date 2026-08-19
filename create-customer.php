<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $phone = trim($body['phone'] ?? '');

    if (!$name && !$email) {
        throw new Exception('Name or email is required.');
    }

    $params = [];
    if ($name)  $params['name']  = $name;
    if ($email) $params['email'] = $email;
    if ($phone) $params['phone'] = $phone;

    $customer = \Stripe\Customer::create($params);

    echo json_encode([
        'success'  => true,
        'customer' => [
            'id'      => $customer->id,
            'name'    => $customer->name  ?? '',
            'email'   => $customer->email ?? '',
            'phone'   => $customer->phone ?? '',
            'created' => $customer->created,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
