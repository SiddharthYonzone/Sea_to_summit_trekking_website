<?php
require_once 'config.php';
$page_title = 'Hotels & Transport';
$active = 'options';
$base = '';

$accommodations = $conn->query("
    SELECT acc.*, COUNT(DISTINCT ta.trek_id) AS trek_count
    FROM accommodations acc
    LEFT JOIN trek_accommodations ta ON ta.accommodation_id = acc.id
    GROUP BY acc.id
    ORDER BY acc.default_price ASC
");

$transports = $conn->query("
    SELECT tr.*, COUNT(DISTINCT tt.trek_id) AS trek_count
    FROM transports tr
    LEFT JOIN trek_transports tt ON tt.transport_id = tr.id
    GROUP BY tr.id
    ORDER BY tr.default_price ASC
");

$defaultAccomImg = 'https://images.unsplash.com/photo-1501876725168-00c445821c9e?w=600';
$defaultTransImg = 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=600';
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb">STAY &amp; GET AROUND</span>
    <h1>HOTELS &amp; <span class="accent">TRANSPORT</span></h1>
    <p>Every accommodation and transportation option we offer -- mix and match when you customise a trek.</p>
</div>

<div class="section" style="padding-top:0;">
    <div class="section-eyebrow">ACCOMMODATION</div>
    <h2>WHERE YOU'LL <span class="accent">STAY</span></h2>
    <div class="trek-grid">
        <?php while ($a = $accommodations->fetch_assoc()): ?>
        <div class="trek-card">
            <div class="trek-card-img">
                <img src="<?= h($a['image_url'] ?: $defaultAccomImg) ?>" alt="<?= h($a['name']) ?>">
            </div>
            <div class="trek-card-body">
                <h3><?= h($a['name']) ?></h3>
                <p style="color:var(--text-muted);font-size:13.5px;text-transform:none;margin-bottom:16px;"><?= h($a['description']) ?></p>
                <div class="trek-price-row">
                    <div class="trek-price">
                        <span class="from">Available on <?= (int)$a['trek_count'] ?> trek<?= (int)$a['trek_count'] === 1 ? '' : 's' ?></span>
                        <span class="amount" style="font-size:16px;"><?= $a['default_price'] == 0 ? 'From free' : 'From +$' . number_format($a['default_price']) ?></span>
                    </div>
                    <a href="treks.php" class="btn btn-outline" style="padding:8px 16px;">BROWSE TREKS &rarr;</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="section" style="padding-top:0;">
    <div class="section-eyebrow">TRANSPORTATION</div>
    <h2>HOW YOU'LL <span class="accent">GET THERE</span></h2>
    <div class="trek-grid">
        <?php while ($t = $transports->fetch_assoc()): ?>
        <div class="trek-card">
            <div class="trek-card-img">
                <img src="<?= h($t['image_url'] ?: $defaultTransImg) ?>" alt="<?= h($t['name']) ?>">
            </div>
            <div class="trek-card-body">
                <h3><?= h($t['name']) ?></h3>
                <p style="color:var(--text-muted);font-size:13.5px;text-transform:none;margin-bottom:16px;"><?= h($t['description']) ?></p>
                <div class="trek-price-row">
                    <div class="trek-price">
                        <span class="from">Available on <?= (int)$t['trek_count'] ?> trek<?= (int)$t['trek_count'] === 1 ? '' : 's' ?></span>
                        <span class="amount" style="font-size:16px;">
                            <?= $t['default_price'] == 0 ? 'From free' : ($t['default_price'] > 0 ? 'From +$' . number_format($t['default_price']) : '-$' . number_format(abs($t['default_price']))) ?>
                        </span>
                    </div>
                    <a href="treks.php" class="btn btn-outline" style="padding:8px 16px;">BROWSE TREKS &rarr;</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
