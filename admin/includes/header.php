<?php
// Expects: $page_title, $admin_active (dashboard|treks|settings)
if (!isset($admin_active)) $admin_active = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' | ' : '' ?>Admin &middot; <?= h(get_setting('site_name', 'Sea to Summit')) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <img class="logo-mark" src="../<?= h(get_setting('logo_white_path', 'assets/images/logo-mark-white.png')) ?>" alt="<?= h(get_setting('site_name', 'Sea to Summit')) ?>">
            <div class="logo-text">
                <span class="logo-title"><?= h(get_setting('site_name', 'Sea to Summit')) ?></span>
                <span class="logo-sub">ADMIN PANEL</span>
            </div>
        </div>

        <nav class="admin-nav">
            <a href="dashboard.php" class="<?= $admin_active === 'dashboard' ? 'active' : '' ?>">
                <span class="ai">&#128203;</span> Bookings
            </a>
            <a href="treks.php" class="<?= $admin_active === 'treks' ? 'active' : '' ?>">
                <span class="ai">&#127956;</span> Treks
            </a>
            <a href="tours.php" class="<?= $admin_active === 'tours' ? 'active' : '' ?>">
                <span class="ai">&#127968;</span> Tours
            </a>
            <a href="accommodations.php" class="<?= $admin_active === 'accommodations' ? 'active' : '' ?>">
                <span class="ai">&#127968;</span> Accommodations
            </a>
            <a href="transports.php" class="<?= $admin_active === 'transports' ? 'active' : '' ?>">
                <span class="ai">&#128662;</span> Transports
            </a>
            <a href="customers.php" class="<?= $admin_active === 'customers' ? 'active' : '' ?>">
                <span class="ai">&#128101;</span> Customers
            </a>
            <a href="reviews.php" class="<?= $admin_active === 'reviews' ? 'active' : '' ?>">
                <span class="ai">&#11088;</span> Reviews
            </a>
            <a href="posts.php" class="<?= $admin_active === 'posts' ? 'active' : '' ?>">
                <span class="ai">&#128240;</span> Info Posts
            </a>
            <a href="settings.php" class="<?= $admin_active === 'settings' ? 'active' : '' ?>">
                <span class="ai">&#9881;</span> Edit Website
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.html" target="_blank" class="btn btn-outline btn-block" style="margin-bottom:10px;">VIEW SITE &#8599;</a>
            <div class="admin-user">Signed in as <b><?= h($_SESSION['admin_username'] ?? '') ?></b></div>
            <a href="../logout.php" class="admin-logout">Logout</a>
        </div>
    </aside>

    <main class="admin-main">
