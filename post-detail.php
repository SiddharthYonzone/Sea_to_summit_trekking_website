<?php
require_once 'config.php';
$active = 'info';
$base = '';

$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM posts WHERE slug = ? AND is_published = 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header('Location: info.php');
    exit;
}
$page_title = $post['title'];
?>
<?php include 'includes/header.php'; ?>

<div class="detail-wrap" style="max-width:800px;">
    <a href="info.php" class="breadcrumb">&larr; ALL INFO</a>

    <?php if ($post['image_url']): ?>
        <div class="detail-gallery-main" style="height:340px;margin-top:20px;margin-bottom:24px;">
            <img src="<?= h($post['image_url']) ?>" alt="<?= h($post['title']) ?>">
        </div>
    <?php endif; ?>

    <div class="detail-top-meta"><?= date('F j, Y', strtotime($post['published_at'])) ?></div>
    <h1 style="font-size:38px;margin-bottom:24px;"><?= h($post['title']) ?></h1>

    <div class="rich-content" style="font-size:16px;">
        <?= nl2br($post['body']) ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
