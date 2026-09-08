<?php
require_once '../config.php';
$page_title = 'Transports';
$admin_active = 'transports';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$transports = $conn->query("
    SELECT a.*, COUNT(ta.id) AS trek_count
    FROM transports a
    LEFT JOIN trek_transports ta ON ta.transport_id = a.id
    GROUP BY a.id
    ORDER BY a.name ASC
");
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>TRANSPORTS</h1>
        <p>A reusable catalog of transport options. Attach any of these to a trek when adding or editing it.</p>
    </div>
    <a href="transport-form.php" class="btn btn-primary">+ ADD TRANSPORT</a>
</div>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Deleted.</div><?php endif; ?>
<?php if (isset($_GET['inuse'])): ?><div class="alert alert-error">Can't delete &mdash; one or more existing bookings reference this transport. Removing it from treks that have no bookings is fine, but you'll need to resolve or delete those bookings first.</div><?php endif; ?>

<?php if ($transports->num_rows === 0): ?>
    <div class="empty-state">No transports yet.</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr><th>Image</th><th>Name</th><th>Description</th><th>Default Price</th><th>Used in</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($a = $transports->fetch_assoc()): ?>
        <tr>
            <td><div class="admin-thumb"><img src="<?= h($a['image_url'] ?: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=200') ?>" alt=""></div></td>
            <td><b><?= h($a['name']) ?></b></td>
            <td style="max-width:280px;"><?= h($a['description']) ?></td>
            <td><?= $a['default_price'] == 0 ? 'Free' : '$' . number_format($a['default_price']) ?></td>
            <td><?= (int)$a['trek_count'] ?> trek<?= (int)$a['trek_count'] === 1 ? '' : 's' ?></td>
            <td>
                <div style="display:flex;gap:8px;">
                    <a href="transport-form.php?id=<?= (int)$a['id'] ?>" class="icon-btn">Edit</a>
                    <form method="POST" action="transport-delete.php">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="icon-btn danger" data-confirm="Delete &quot;<?= h(addslashes($a['name'])) ?>&quot;?">Delete</button>
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
