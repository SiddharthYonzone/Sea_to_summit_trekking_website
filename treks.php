<?php
require_once 'config.php';
$page_title = 'Treks';
$active = 'treks';
$base = '';

$result = $conn->query("SELECT * FROM treks WHERE product_type = 'trek' ORDER BY id ASC");
$count = $result->num_rows;
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb">ALL ROUTES</span>
    <h1>OUR <span class="accent">TREKS</span></h1>
    <p><?= $count ?> expeditions available &middot; Fully customisable</p>
</div>

<div class="filters-bar">
    <div class="search-box">
        <span>&#128269;</span>
        <input type="text" id="trekSearch" placeholder="Search treks...">
    </div>
    <div class="filter-group">
        <button class="filter-pill active" data-difficulty="ALL">ALL</button>
        <button class="filter-pill" data-difficulty="Easy">EASY</button>
        <button class="filter-pill" data-difficulty="Moderate">MODERATE</button>
        <button class="filter-pill" data-difficulty="Challenging">CHALLENGING</button>
        <button class="filter-pill" data-difficulty="Extreme">EXTREME</button>
    </div>
    <div class="filter-group">
        <button class="filter-pill active" data-duration="ALL">ALL</button>
        <button class="filter-pill" data-duration="1-7">1-7 DAYS</button>
        <button class="filter-pill" data-duration="8-14">8-14 DAYS</button>
        <button class="filter-pill" data-duration="15+">15+ DAYS</button>
    </div>
</div>

<div class="treks-listing-grid">
    <?php while ($t = $result->fetch_assoc()): ?>
    <a href="trek-detail.php?slug=<?= h($t['slug']) ?>" class="trek-card"
       data-title="<?= h($t['title']) ?>" data-region="<?= h($t['region']) ?>"
       data-difficulty="<?= h($t['difficulty']) ?>" data-days="<?= (int)$t['duration_days'] ?>">
        <div class="trek-card-img">
            <img src="<?= h($t['image_url']) ?>" alt="<?= h($t['title']) ?>">
            <?php if ($t['badge']): ?><div class="trek-badge"><?= h($t['badge']) ?></div><?php endif; ?>
            <div class="trek-difficulty <?= h($t['difficulty']) ?>"><?= h($t['difficulty']) ?></div>
        </div>
        <div class="trek-card-body">
            <div class="trek-region"><?= h($t['region']) ?> &middot; <?= h($t['country']) ?></div>
            <h3><?= h($t['title']) ?></h3>
            <div class="trek-meta">
                <span>&#9201; <?= (int)$t['duration_days'] ?> days</span>
                <span>&#9968; <?= number_format($t['max_altitude']) ?>m</span>
                <span>&#9733; <?= h($t['rating']) ?></span>
            </div>
            <div class="trek-price-row">
                <div class="trek-price"><span class="from">From</span><span class="amount">$<?= number_format($t['base_price']) ?>/person</span></div>
                <span class="btn btn-outline" style="padding:8px 16px;">CUSTOMISE &rarr;</span>
            </div>
        </div>
    </a>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
