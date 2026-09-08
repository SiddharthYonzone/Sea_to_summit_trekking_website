<?php
require_once '../config.php';
$page_title = 'Customers';
$admin_active = 'customers';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$customers = $conn->query("
    SELECT c.*, COUNT(b.id) AS booking_count
    FROM customers c
    LEFT JOIN bookings b ON b.customer_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
?>
<?php include 'includes/header.php'; ?>

<div class="admin-page-header">
    <div>
        <h1>CUSTOMERS</h1>
        <p>Everyone who has created an account on the site.</p>
    </div>
</div>

<?php if ($customers->num_rows === 0): ?>
    <div class="empty-state">No registered customers yet.</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="data-table">
    <thead>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Joined</th></tr>
    </thead>
    <tbody>
    <?php while ($c = $customers->fetch_assoc()): ?>
        <tr>
            <td><?= h($c['full_name']) ?></td>
            <td><?= h($c['email']) ?></td>
            <td><?= h($c['phone'] ?: '&mdash;') ?></td>
            <td><?= (int)$c['booking_count'] ?></td>
            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
