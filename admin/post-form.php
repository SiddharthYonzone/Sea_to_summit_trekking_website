<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$admin_active = 'posts';
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$page_title = $isEdit ? 'Edit Post' : 'Add Post';
$errors = [];

$post = ['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'image_url' => '', 'is_published' => 1, 'published_at' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post['title'] = trim($_POST['title'] ?? '');
    $post['slug'] = trim($_POST['slug'] ?? '') ?: slugify($post['title']);
    $post['excerpt'] = trim($_POST['excerpt'] ?? '');
    $post['body'] = trim($_POST['body'] ?? '');
    $post['image_url'] = trim($_POST['image_url'] ?? '') ?: null;
    $post['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $post['published_at'] = $_POST['published_at'] ?? date('Y-m-d');

    if ($post['title'] === '') $errors[] = 'Title is required.';
    if ($post['body'] === '') $errors[] = 'Body is required.';

    $slugCheck = $conn->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
    $slugCheck->bind_param('si', $post['slug'], $id);
    $slugCheck->execute();
    if ($slugCheck->get_result()->num_rows > 0) {
        $post['slug'] .= '-' . substr(md5(uniqid()), 0, 5);
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE posts SET slug=?, title=?, excerpt=?, body=?, image_url=?, is_published=?, published_at=? WHERE id=?");
            $stmt->bind_param('sssssisi', $post['slug'], $post['title'], $post['excerpt'], $post['body'], $post['image_url'], $post['is_published'], $post['published_at'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO posts (slug, title, excerpt, body, image_url, is_published, published_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssis', $post['slug'], $post['title'], $post['excerpt'], $post['body'], $post['image_url'], $post['is_published'], $post['published_at']);
        }
        $stmt->execute();
        header('Location: posts.php?saved=1');
        exit;
    }
} elseif ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) { header('Location: posts.php'); exit; }
    $post = $found;
}
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div><h1><?= $isEdit ? 'EDIT POST' : 'ADD POST' ?></h1></div>
    <a href="posts.php" class="btn btn-outline">&larr; BACK TO POSTS</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-card">
        <div class="admin-form-grid">
            <div class="form-field">
                <label>Title</label>
                <input type="text" id="titleInput" name="title" value="<?= h($post['title']) ?>" required>
            </div>
            <div class="form-field">
                <label>URL Slug</label>
                <input type="text" id="slugInput" name="slug" value="<?= h($post['slug']) ?>" placeholder="auto-generated-from-title">
            </div>
            <div class="form-field">
                <label>Published Date</label>
                <input type="date" name="published_at" value="<?= h($post['published_at']) ?>">
            </div>
            <div class="form-field checkbox-field">
                <input type="checkbox" id="isPublished" name="is_published" <?= $post['is_published'] ? 'checked' : '' ?>>
                <label for="isPublished" style="margin:0;text-transform:none;">Published (visible on the site)</label>
            </div>
            <div class="form-field full">
                <label>Image URL (optional)</label>
                <input type="text" name="image_url" value="<?= h($post['image_url']) ?>">
            </div>
            <div class="form-field full">
                <label>Excerpt (short summary shown on the Info list page)</label>
                <input type="text" name="excerpt" value="<?= h($post['excerpt']) ?>" maxlength="300">
            </div>
            <div class="form-field full">
                <label>Body</label>
                <div id="bodyEditor" class="rich-editor" style="min-height:300px;"></div>
                <textarea id="bodyInput" name="body" style="display:none;" required><?= h($post['body']) ?></textarea>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'SAVE CHANGES' : 'PUBLISH POST' ?></button>
        <a href="posts.php" class="btn btn-outline">CANCEL</a>
    </div>
</form>

<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/2.0.3/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/2.0.3/quill.min.js"></script>

<?php include 'includes/footer.php'; ?>
