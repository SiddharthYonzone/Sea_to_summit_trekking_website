<?php
require_once '../config.php';
$page_title = 'Reviews';
$admin_active = 'reviews';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$reviews = $conn->query("SELECT * FROM reviews ORDER BY sort_order ASC, id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>REVIEWS</h1>
        <p>Manage the Google-style reviews shown on the homepage. Set your real Google review link and rating in Edit Website &rarr; General.</p>
    </div>
    <a href="review-form.php" class="btn btn-primary">+ ADD REVIEW</a>
</div>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Review saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Review deleted.</div><?php endif; ?>

<?php if ($reviews->num_rows === 0): ?>
    <div class="empty-state">No reviews yet.</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr><th>Order</th><th>Reviewer</th><th>Rating</th><th>Review</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($r = $reviews->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$r['sort_order'] ?></td>
            <td><?= h($r['reviewer_name']) ?></td>
            <td><?= str_repeat('&#9733;', (int)$r['rating']) ?></td>
            <td style="max-width:320px;"><?= h(mb_strimwidth($r['review_text'], 0, 90, '...')) ?></td>
            <td><?= $r['review_date'] ? date('M j, Y', strtotime($r['review_date'])) : '&mdash;' ?></td>
            <td>
                <div style="display:flex;gap:8px;">
                    <a href="review-form.php?id=<?= (int)$r['id'] ?>" class="icon-btn">Edit</a>
                    <form method="POST" action="review-delete.php">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="icon-btn danger" data-confirm="Delete this review?">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
