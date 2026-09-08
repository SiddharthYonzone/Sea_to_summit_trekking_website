<?php
require_once '../config.php';
$page_title = 'Treks';
$admin_active = 'treks';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$treks = $conn->query("SELECT * FROM treks WHERE product_type = 'trek' ORDER BY id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>TREKS</h1>
        <p>Add, edit, or remove trek packages. Changes go live immediately.</p>
    </div>
    <a href="trek-form.php?type=trek" class="btn btn-primary">+ ADD NEW TREK</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Trek deleted.</div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Trek saved successfully.</div>
<?php endif; ?>

<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr>
            <th>Image</th><th>Title</th><th>Region</th><th>Difficulty</th>
            <th>Duration</th><th>Price</th><th>Badge</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($t = $treks->fetch_assoc()): ?>
        <tr>
            <td><div class="admin-thumb"><img src="<?= h($t['image_url']) ?>" alt=""></div></td>
            <td><b><?= h($t['title']) ?></b><br><span style="color:var(--text-muted);font-size:11px;">/<?= h($t['slug']) ?></span></td>
            <td><?= h($t['region']) ?></td>
            <td><span class="diff-tag <?= h($t['difficulty']) ?>" style="font-size:11px;padding:4px 10px;"><?= h($t['difficulty']) ?></span></td>
            <td><?= (int)$t['duration_days'] ?> days</td>
            <td>$<?= number_format($t['base_price']) ?></td>
            <td><?= $t['badge'] ? h($t['badge']) : '&mdash;' ?></td>
            <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="../trek-detail.php?slug=<?= h($t['slug']) ?>" target="_blank" class="icon-btn">View</a>
                    <a href="trek-form.php?id=<?= (int)$t['id'] ?>" class="icon-btn">Edit</a>
                    <form method="POST" action="trek-delete.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="icon-btn danger" data-confirm="Delete &quot;<?= h(addslashes($t['title'])) ?>&quot;? This also deletes any bookings made for this trek. This cannot be undone.">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; ?>
