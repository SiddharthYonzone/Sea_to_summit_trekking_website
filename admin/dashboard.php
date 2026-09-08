<?php
require_once '../config.php';
$page_title = 'Bookings';
$admin_active = 'dashboard';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $bid = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    if (in_array($status, ['Pending', 'Confirmed', 'Cancelled'])) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $bid);
        $stmt->execute();
    }
    header('Location: dashboard.php');
    exit;
}

$bookings = $conn->query("
    SELECT b.*, t.title AS trek_title, a.name AS accom_name, tr.name AS trans_name
    FROM bookings b
    JOIN treks t ON b.trek_id = t.id
    JOIN trek_accommodations ta ON b.accommodation_id = ta.id
    JOIN accommodations a ON ta.accommodation_id = a.id
    JOIN trek_transports tt ON b.transport_id = tt.id
    JOIN transports tr ON tt.transport_id = tr.id
    ORDER BY b.created_at DESC
");

$totalBookings = $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
$pendingCount  = $conn->query("SELECT COUNT(*) c FROM bookings WHERE status='Pending'")->fetch_assoc()['c'];
$confirmedCount = $conn->query("SELECT COUNT(*) c FROM bookings WHERE status='Confirmed'")->fetch_assoc()['c'];
$revenue = $conn->query("SELECT SUM(total_price) s FROM bookings WHERE status != 'Cancelled'")->fetch_assoc()['s'] ?? 0;
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>BOOKINGS</h1>
        <p>Every booking request submitted through the site.</p>
    </div>
</div>

<section class="stats-bar" style="border:1px solid var(--border);border-radius:8px;padding:30px;margin-bottom:30px;">
    <div class="stats-grid">
        <div><div class="stat-num"><?= (int)$totalBookings ?></div><div class="stat-label">TOTAL BOOKINGS</div></div>
        <div><div class="stat-num"><?= (int)$pendingCount ?></div><div class="stat-label">PENDING</div></div>
        <div><div class="stat-num"><?= (int)$confirmedCount ?></div><div class="stat-label">CONFIRMED</div></div>
        <div><div class="stat-num">$<?= number_format($revenue) ?></div><div class="stat-label">TOTAL VALUE</div></div>
    </div>
</section>

<?php if ($bookings->num_rows === 0): ?>
    <div class="empty-state">No bookings yet.</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th><th>Trek</th><th>Traveller</th><th>Contact</th>
            <th>Group</th><th>Start Date</th><th>Accommodation</th><th>Transport</th>
            <th>Total</th><th>Status</th><th>Update</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($b = $bookings->fetch_assoc()): ?>
        <tr>
            <td>#<?= (int)$b['id'] ?></td>
            <td><?= h($b['trek_title']) ?></td>
            <td><?= h($b['full_name']) ?></td>
            <td><?= h($b['email']) ?><br><span style="color:var(--text-muted)"><?= h($b['phone']) ?></span></td>
            <td><?= (int)$b['group_size'] ?></td>
            <td><?= h($b['start_date']) ?></td>
            <td><?= h($b['accom_name']) ?></td>
            <td><?= h($b['trans_name']) ?></td>
            <td>$<?= number_format($b['total_price']) ?></td>
            <td><span class="status-pill <?= h($b['status']) ?>"><?= h($b['status']) ?></span></td>
            <td>
                <form method="POST" style="display:flex;gap:6px;">
                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <select name="status" onchange="this.form.submit()" style="background:var(--navy);color:var(--text-light);border:1px solid var(--border);border-radius:4px;padding:4px;">
                        <option value="Pending" <?= $b['status']==='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Confirmed" <?= $b['status']==='Confirmed'?'selected':'' ?>>Confirmed</option>
                        <option value="Cancelled" <?= $b['status']==='Cancelled'?'selected':'' ?>>Cancelled</option>
                    </select>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
