<?php
require_once 'config.php';
$page_title = 'My Bookings';
$active = 'bookings';
$base = '';

$bookings = null;
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $searched = true;
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("
        SELECT b.*, t.title AS trek_title, t.slug, a.name AS accom_name, tr.name AS trans_name
        FROM bookings b
        JOIN treks t ON b.trek_id = t.id
        JOIN trek_accommodations ta ON b.accommodation_id = ta.id
        JOIN accommodations a ON ta.accommodation_id = a.id
        JOIN trek_transports tt ON b.transport_id = tt.id
        JOIN transports tr ON tt.transport_id = tr.id
        WHERE b.email = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $bookings = $stmt->get_result();
}
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb">YOUR TRIPS</span>
    <h1>MY <span class="accent">BOOKINGS</span></h1>
    <p>Enter the email you booked with to see your trek bookings and status.</p>
</div>

<div class="table-wrap">
    <form method="POST" class="lookup-form">
        <input type="email" name="email" placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>
        <button type="submit" class="btn btn-primary">FIND BOOKINGS</button>
    </form>

    <?php if ($searched): ?>
        <?php if ($bookings && $bookings->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr><th>Trek</th><th>Start Date</th><th>Group</th><th>Accommodation</th><th>Transport</th><th>Total</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php while ($b = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><a href="trek-detail.php?slug=<?= h($b['slug']) ?>" style="color:var(--blue);"><?= h($b['trek_title']) ?></a></td>
                        <td><?= h($b['start_date']) ?></td>
                        <td><?= (int)$b['group_size'] ?></td>
                        <td><?= h($b['accom_name']) ?></td>
                        <td><?= h($b['trans_name']) ?></td>
                        <td>$<?= number_format($b['total_price']) ?></td>
                        <td><span class="status-pill <?= h($b['status']) ?>"><?= h($b['status']) ?></span></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No bookings found for that email address.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
