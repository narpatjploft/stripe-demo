<?php
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
require __DIR__ . '/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$setupIntentId = $input['setup_intent_id'] ?? null;

if (!$setupIntentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing setup_intent_id']);
    exit;
}

try {
    $setupIntent = SetupIntent::retrieve($setupIntentId);

    if (!empty($setupIntent->payment_method)) {
        try {
            $pm = PaymentMethod::retrieve($setupIntent->payment_method);
            $pm->detach();
        } catch (Exception $e) {
            // It may not be attached yet. Ignore detach failure.
        }
    }

    try {
        $setupIntent->cancel();
    } catch (Exception $e) {
        // SetupIntent may not be cancellable in some states.
    }

    echo json_encode([
        'success' => true,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}