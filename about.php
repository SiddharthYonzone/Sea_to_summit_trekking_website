<?php
require_once 'config.php';
$page_title = 'About Us';
$active = 'about';
$base = '';
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb"><?= h(get_setting('about_eyebrow', 'OUR STORY')) ?></span>
    <h1><?= h(get_setting('about_heading', 'ABOUT US')) ?></h1>
</div>

<div class="section" style="padding-top:20px;">
    <div class="why-section">
        <div class="why-img" style="height:420px;">
            <img src="<?= h(get_setting('about_image')) ?>" alt="About us">
        </div>
        <div>
            <?php foreach (explode("\n\n", get_setting('about_body', '')) as $para): if (trim($para) === '') continue; ?>
                <p style="color:var(--text-muted);font-size:15px;margin-bottom:18px;text-transform:none;line-height:1.7;"><?= nl2br(h(trim($para))) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<section class="stats-bar">
    <div class="stats-grid">
        <div><div class="stat-num"><?= h(get_setting('stat_years')) ?></div><div class="stat-label"><?= h(get_setting('stat_years_label')) ?></div></div>
        <div><div class="stat-num"><?= h(get_setting('stat_trekkers')) ?></div><div class="stat-label"><?= h(get_setting('stat_trekkers_label')) ?></div></div>
        <div><div class="stat-num"><?= h(get_setting('stat_routes')) ?></div><div class="stat-label"><?= h(get_setting('stat_routes_label')) ?></div></div>
        <div><div class="stat-num"><?= h(get_setting('stat_safety')) ?></div><div class="stat-label"><?= h(get_setting('stat_safety_label')) ?></div></div>
    </div>
</section>

<section class="section section-light">
    <div style="max-width:700px;">
        <div class="section-eyebrow">OUR MISSION</div>
        <h2 style="font-size:30px;margin-bottom:18px;"><?= h(get_setting('about_mission_title', 'OUR MISSION')) ?></h2>
        <p style="color:#56637a;font-size:16px;text-transform:none;line-height:1.7;"><?= h(get_setting('about_mission_body')) ?></p>
    </div>
</section>

<section class="cta-banner">
    <div class="cta-inner">
        <div>
            <h2>READY TO<br><span class="accent" style="color:var(--blue)">SUMMIT</span> SOMETHING?</h2>
        </div>
        <div class="cta-actions">
            <a href="treks.php" class="btn btn-primary">VIEW TREKS</a>
            <a href="https://wa.me/<?= h(get_setting('whatsapp_number', WHATSAPP_NUMBER)) ?>" target="_blank" class="btn btn-outline">WHATSAPP US</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
