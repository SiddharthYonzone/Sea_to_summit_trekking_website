<?php
require_once 'config.php';
$page_title = 'My Account';
$base = '';

if (!is_customer_logged_in()) {
    header('Location: account-login.php?redirect=account.php');
    exit;
}

$customer = current_customer();
if (!$customer) {
    // Session pointed at a customer that no longer exists
    unset($_SESSION['customer_id']);
    header('Location: account-login.php');
    exit;
}

// Bookings tied to this account, PLUS any guest bookings made earlier under the same email
$stmt = $conn->prepare("
    SELECT b.*, t.title AS trek_title, t.slug, a.name AS accom_name, tr.name AS trans_name
    FROM bookings b
    JOIN treks t ON b.trek_id = t.id
    JOIN trek_accommodations ta ON b.accommodation_id = ta.id
    JOIN accommodations a ON ta.accommodation_id = a.id
    JOIN trek_transports tt ON b.transport_id = tt.id
    JOIN transports tr ON tt.transport_id = tr.id
    WHERE b.customer_id = ? OR b.email = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param('is', $customer['id'], $customer['email']);
$stmt->execute();
$bookings = $stmt->get_result();

$deleted = isset($_GET['deleted']);
$updated = isset($_GET['updated']);

// Handle profile updates
$profile_updated = false;
$profile_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($full_name === '' || $email === '' || $phone === '') {
        $profile_error = 'Name, email, and phone are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = 'Please enter a valid email address.';
    } else {
        // Check if email is already taken by another user
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $check->bind_param('si', $email, $customer['id']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $profile_error = 'That email is already associated with another account.';
        } else {
            // Update profile
            $stmt = $conn->prepare("UPDATE customers SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param('sssi', $full_name, $email, $phone, $customer['id']);
            $stmt->execute();

            // Refresh customer data
            $customer = current_customer(); // This will be re-fetched
            $profile_updated = true;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <span class="breadcrumb">YOUR ACCOUNT</span>
    <h1>MY <span class="accent">ACCOUNT</span></h1>
    <p>Welcome back, <?= h($customer['full_name']) ?>.</p>
</div>

<?php if ($deleted): ?>
    <div class="table-wrap" style="padding-bottom:0;"><div class="alert alert-success">Booking deleted.</div></div>
<?php elseif ($updated): ?>
    <div class="table-wrap" style="padding-bottom:0;"><div class="alert alert-success">Booking updated.</div></div>
<?php endif; ?>

<div class="table-wrap">
    <h3 style="font-size:15px;color:var(--blue);margin-bottom:16px;">YOUR BOOKINGS</h3>

    <?php if ($bookings->num_rows === 0): ?>
        <div class="empty-state">
            You don't have any bookings yet. <a href="treks.php" style="color:var(--blue);">Browse treks &rarr;</a>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Trek</th><th>Start Date</th><th>Group</th><th>Accommodation</th><th>Transport</th><th>Total</th><th>Status</th><th>Actions</th></tr>
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
                    <td>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="booking-confirmation.php?id=<?= (int)$b['id'] ?>&code=<?= urlencode(booking_confirmation_code($b['id'])) ?>" class="icon-btn icon-only" title="View booking confirmation" aria-label="View booking confirmation">&#128065;</a>
                            <a href="booking-edit.php?id=<?= (int)$b['id'] ?>" class="icon-btn">Edit</a>
                            <form method="POST" action="booking-delete.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                <button type="submit" class="icon-btn danger" data-confirm="Delete your booking for <?= h(addslashes($b['trek_title'])) ?>? This cannot be undone.">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="table-wrap" style="padding-top:0;">
    <h3 style="font-size:15px;color:var(--blue);margin-bottom:16px;">PROFILE</h3>

    <?php if ($profile_updated): ?>
        <div class="alert alert-success" style="margin-bottom:16px;">Profile updated successfully.</div>
    <?php endif; ?>

    <?php if ($profile_error): ?>
        <div class="alert alert-error" style="margin-bottom:16px;"><?= h($profile_error) ?></div>
    <?php endif; ?>

    <form method="POST" style="max-width:500px;">
        <input type="hidden" name="update_profile" value="1">

        <div class="form-field">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= h($customer['full_name']) ?>" required>
        </div>

        <div class="form-field">
            <label>Email</label>
            <input type="email" name="email" value="<?= h($customer['email']) ?>" required>
        </div>

        <div class="form-field">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= h($customer['phone'] ?? '') ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">SAVE CHANGES</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
