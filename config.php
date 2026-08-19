<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$secretKey      = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: '';
$publishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_PUBLISHABLE_KEY') ?: '';

if ($secretKey === '') {
    throw new RuntimeException(
        'STRIPE_SECRET_KEY is not set. Copy .env.example to .env and add your Stripe keys.'
    );
}

\Stripe\Stripe::setApiKey($secretKey);

define('STRIPE_PUBLISHABLE_KEY', $publishableKey);
