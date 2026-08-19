<?php

require __DIR__ . '/app.php';

$pageTitle = $pageTitle ?? 'Stripe';
$pageCss   = $pageCss   ?? '';
$extraHead = $extraHead ?? '';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/common.css">
    <?php if ($pageCss): ?>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/<?= htmlspecialchars($pageCss) ?>">
    <?php endif; ?>
    <?= $extraHead ?>
    <script>const BASE = <?= json_encode(APP_BASE) ?>;</script>
</head>

<body>
