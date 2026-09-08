<?php
// Expects (optionally) $active to be set to one of: home, treks, options, about, info
if (!isset($active)) $active = '';
$b = isset($base) ? $base : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' | ' : '' ?><?= h(get_setting('site_name', 'Sea to Summit')) ?> <?= h(get_setting('site_name_sub', 'Trekking')) ?></title>
<link rel="stylesheet" href="<?= $b ?>assets/css/style.css">
<link rel="icon" type="image/png" href="<?= $b ?>assets/images/favicon.png">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="<?= $b ?>index.html" class="logo">
            <img class="logo-mark" src="<?= $b ?><?= h(get_setting('logo_white_path', 'assets/images/logo-mark-white.png')) ?>" alt="<?= h(get_setting('site_name', 'Sea to Summit')) ?>">
            <div class="logo-text">
                <span class="logo-title"><?= strtoupper(h(get_setting('site_name', 'Sea to Summit'))) ?></span>
                <span class="logo-sub"><?= strtoupper(h(get_setting('site_name_sub', 'Trekking'))) ?></span>
            </div>
        </a>

        <nav class="main-nav" id="mainNav">
            <a href="<?= $b ?>index.html" class="<?= $active === 'home' ? 'active' : '' ?>">HOME</a>
            <a href="<?= $b ?>treks.php" class="<?= $active === 'treks' ? 'active' : '' ?>">TREKS</a>
            <a href="<?= $b ?>tours.php" class="<?= $active === 'tours' ? 'active' : '' ?>">TOURS</a>
            <a href="<?= $b ?>travel-options.php" class="<?= $active === 'options' ? 'active' : '' ?>">HOTELS &amp; TRANSPORT</a>
            <a href="<?= $b ?>about.php" class="<?= $active === 'about' ? 'active' : '' ?>">ABOUT</a>
            <a href="<?= $b ?>info.php" class="<?= $active === 'info' ? 'active' : '' ?>">INFO</a>
        </nav>

        <div class="header-actions">
            <?php if (is_admin_logged_in()): ?>
                <a href="<?= $b ?>admin/dashboard.php" class="btn btn-outline">ADMIN</a>
                <a href="<?= $b ?>logout.php" class="btn btn-outline" style="border-color:#e74c3c;color:#e74c3c;">LOGOUT</a>
            <?php elseif (is_customer_logged_in()): ?>
                <a href="<?= $b ?>account.php" class="btn btn-outline">MY ACCOUNT</a>
                <a href="<?= $b ?>account-logout.php" class="btn btn-outline" style="border-color:#e74c3c;color:#e74c3c;">LOGOUT</a>
            <?php else: ?>
                <a href="<?= $b ?>account-login.php" class="btn btn-outline">LOGIN</a>
            <?php endif; ?>
            <a href="<?= $b ?>treks.php" class="btn btn-primary">BOOK NOW</a>
            <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
