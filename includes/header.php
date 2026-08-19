<?php

require_once __DIR__ . '/app.php';

$pageHeading  = $pageHeading  ?? '';
$pageSubtitle = $pageSubtitle ?? '';
$currentPage  = $currentPage  ?? '';
?>
<header class="site-header">
    <div class="site-header-inner">
        <a class="site-brand" href="<?= APP_BASE ?>/dashboard.php">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <span>Stripe Demo</span>
        </a>
        <nav class="header-nav" aria-label="Main navigation">
            <?php foreach (app_nav_items() as $key => $item): ?>
            <a class="nav-link<?= $currentPage === $key ? ' active' : '' ?>" href="<?= $item['href'] ?>">
                <?= $item['icon'] ?>
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="site-main">
<div class="container">

    <div class="page-header">
        <h1><?= htmlspecialchars($pageHeading) ?></h1>
        <?php if ($pageSubtitle): ?>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>
