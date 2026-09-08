<?php
require_once 'config.php';
$page_title = 'Info';
$active = 'info';
$base = '';

$posts = $conn->query("SELECT * FROM posts WHERE is_published = 1 ORDER BY published_at DESC, id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb">NEWS &amp; UPDATES</span>
    <h1>TRAIL <span class="accent">INFO</span></h1>
    <p>Announcements, trail conditions, and updates from the Sea to Summit team.</p>
</div>

<div class="treks-listing-grid" style="padding-top:10px;">
    <?php if ($posts->num_rows === 0): ?>
        <div class="empty-state">No posts yet — check back soon.</div>
    <?php endif; ?>
    <?php while ($p = $posts->fetch_assoc()): ?>
        <a href="post-detail.php?slug=<?= h($p['slug']) ?>" class="trek-card">
            <?php if ($p['image_url']): ?>
            <div class="trek-card-img" style="height:170px;">
                <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['title']) ?>">
            </div>
            <?php endif; ?>
            <div class="trek-card-body">
                <div class="trek-region"><?= date('F j, Y', strtotime($p['published_at'])) ?></div>
                <h3 style="font-size:18px;"><?= h($p['title']) ?></h3>
                <p style="color:var(--text-muted);font-size:13.5px;text-transform:none;margin-bottom:16px;"><?= h($p['excerpt']) ?></p>
                <span class="btn btn-outline" style="padding:8px 16px;align-self:flex-start;">READ MORE &rarr;</span>
            </div>
        </a>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
