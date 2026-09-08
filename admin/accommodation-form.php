<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$admin_active = 'accommodations';
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$page_title = $isEdit ? 'Edit Accommodation' : 'Add Accommodation';
$errors = [];

$item = ['name' => '', 'description' => '', 'image_url' => '', 'default_price' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['name'] = trim($_POST['name'] ?? '');
    $item['description'] = trim($_POST['description'] ?? '');
    $item['image_url'] = trim($_POST['image_url'] ?? '') ?: null;
    $item['default_price'] = (float)($_POST['default_price'] ?? 0);

    if ($item['name'] === '') $errors[] = 'Name is required.';

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE accommodations SET name=?, description=?, image_url=?, default_price=? WHERE id=?");
            $stmt->bind_param('sssdi', $item['name'], $item['description'], $item['image_url'], $item['default_price'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO accommodations (name, description, image_url, default_price) VALUES (?,?,?,?)");
            $stmt->bind_param('sssd', $item['name'], $item['description'], $item['image_url'], $item['default_price']);
        }
        $stmt->execute();
        header('Location: accommodations.php?saved=1');
        exit;
    }
} elseif ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM accommodations WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) { header('Location: accommodations.php'); exit; }
    $item = $found;
}
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div><h1><?= $isEdit ? 'EDIT ACCOMMODATION' : 'ADD ACCOMMODATION' ?></h1></div>
    <a href="accommodations.php" class="btn btn-outline">&larr; BACK TO ACCOMMODATIONS</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-card">
        <div class="admin-form-grid">
            <div class="form-field">
                <label>Name</label>
                <input type="text" name="name" value="<?= h($item['name']) ?>" required>
            </div>
            <div class="form-field">
                <label>Default Extra Price (USD)</label>
                <input type="number" step="0.01" name="default_price" value="<?= h($item['default_price']) ?>">
                <div class="hint">Used as the starting price when you attach this to a new trek — you can still override it per trek.</div>
            </div>
            <div class="form-field full">
                <label>Image URL (optional)</label>
                <input type="text" name="image_url" value="<?= h($item['image_url']) ?>">
            </div>
            <div class="form-field full">
                <label>Description</label>
                <textarea name="description" rows="3"><?= h($item['description']) ?></textarea>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'SAVE CHANGES' : 'ADD ACCOMMODATION' ?></button>
        <a href="accommodations.php" class="btn btn-outline">CANCEL</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
