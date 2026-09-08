<?php
require_once '../config.php';
$page_title = 'Info Posts';
$admin_active = 'posts';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$posts = $conn->query("SELECT * FROM posts ORDER BY published_at DESC, id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>INFO POSTS</h1>
        <p>Announcements, trail conditions, promotions -- anything you want to publish on the public Info page.</p>
    </div>
    <a href="post-form.php" class="btn btn-primary">+ ADD POST</a>
</div>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Post saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Post deleted.</div><?php endif; ?>

<?php if ($posts->num_rows === 0): ?>
    <div class="empty-state">No posts yet.</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr><th>Title</th><th>Published</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($p = $posts->fetch_assoc()): ?>
        <tr>
            <td><b><?= h($p['title']) ?></b><br><span style="color:var(--text-muted);font-size:11px;">/<?= h($p['slug']) ?></span></td>
            <td><?= date('M j, Y', strtotime($p['published_at'])) ?></td>
            <td><span class="status-pill <?= $p['is_published'] ? 'Confirmed' : 'Cancelled' ?>"><?= $p['is_published'] ? 'Published' : 'Draft' ?></span></td>
            <td>
                <div style="display:flex;gap:8px;">
                    <a href="../post-detail.php?slug=<?= h($p['slug']) ?>" target="_blank" class="icon-btn">View</a>
                    <a href="post-form.php?id=<?= (int)$p['id'] ?>" class="icon-btn">Edit</a>
                    <form method="POST" action="post-delete.php">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="icon-btn danger" data-confirm="Delete &quot;<?= h(addslashes($p['title'])) ?>&quot;?">Delete</button>
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
