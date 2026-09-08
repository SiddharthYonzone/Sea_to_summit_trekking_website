<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$admin_active = 'reviews';
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$page_title = $isEdit ? 'Edit Review' : 'Add Review';

$review = ['reviewer_name' => '', 'rating' => 5, 'review_text' => '', 'review_date' => date('Y-m-d'), 'avatar_url' => '', 'sort_order' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review['reviewer_name'] = trim($_POST['reviewer_name'] ?? '');
    $review['rating'] = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $review['review_text'] = trim($_POST['review_text'] ?? '');
    $review['review_date'] = $_POST['review_date'] ?? date('Y-m-d');
    $review['avatar_url'] = trim($_POST['avatar_url'] ?? '') ?: null;
    $review['sort_order'] = (int)($_POST['sort_order'] ?? 0);

    if ($review['reviewer_name'] !== '' && $review['review_text'] !== '') {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE reviews SET reviewer_name=?, rating=?, review_text=?, review_date=?, avatar_url=?, sort_order=? WHERE id=?");
            $stmt->bind_param('sisssii', $review['reviewer_name'], $review['rating'], $review['review_text'], $review['review_date'], $review['avatar_url'], $review['sort_order'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (reviewer_name, rating, review_text, review_date, avatar_url, sort_order) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('sisssi', $review['reviewer_name'], $review['rating'], $review['review_text'], $review['review_date'], $review['avatar_url'], $review['sort_order']);
        }
        $stmt->execute();
        header('Location: reviews.php?saved=1');
        exit;
    }
} elseif ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) { header('Location: reviews.php'); exit; }
    $review = $found;
}
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div><h1><?= $isEdit ? 'EDIT REVIEW' : 'ADD REVIEW' ?></h1></div>
    <a href="reviews.php" class="btn btn-outline">&larr; BACK TO REVIEWS</a>
</div>

<form method="POST">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-card">
        <div class="admin-form-grid">
            <div class="form-field">
                <label>Reviewer Name</label>
                <input type="text" name="reviewer_name" value="<?= h($review['reviewer_name']) ?>" required>
            </div>
            <div class="form-field">
                <label>Rating (1-5)</label>
                <select name="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= (int)$review['rating'] === $i ? 'selected' : '' ?>><?= $i ?> stars</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Review Date</label>
                <input type="date" name="review_date" value="<?= h($review['review_date']) ?>">
            </div>
            <div class="form-field">
                <label>Sort Order (lower = shown first)</label>
                <input type="number" name="sort_order" value="<?= (int)$review['sort_order'] ?>">
            </div>
            <div class="form-field full">
                <label>Avatar Image URL (optional — leave blank for auto-generated initials)</label>
                <input type="text" name="avatar_url" value="<?= h($review['avatar_url']) ?>">
            </div>
            <div class="form-field full">
                <label>Review Text</label>
                <textarea name="review_text" rows="4" required><?= h($review['review_text']) ?></textarea>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'SAVE CHANGES' : 'ADD REVIEW' ?></button>
        <a href="reviews.php" class="btn btn-outline">CANCEL</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
