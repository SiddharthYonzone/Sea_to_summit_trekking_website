<?php
require_once 'config.php';
$page_title = 'Edit Booking';
$base = '';

if (!is_customer_logged_in()) {
    header('Location: account-login.php?redirect=account.php');
    exit;
}

$customer = current_customer();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Load the booking and verify it belongs to this customer (by customer_id OR matching email)
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND (customer_id = ? OR email = ?)");
$stmt->bind_param('iis', $id, $customer['id'], $customer['email']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: account.php');
    exit;
}

$trekStmt = $conn->prepare("SELECT * FROM treks WHERE id = ?");
$trekStmt->bind_param('i', $booking['trek_id']);
$trekStmt->execute();
$trek = $trekStmt->get_result()->fetch_assoc();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accommodation_id = (int)($_POST['accommodation_id'] ?? 0);
    $transport_id = (int)($_POST['transport_id'] ?? 0);
    $group_size = max(1, (int)($_POST['group_size'] ?? 1));
    $start_date = $_POST['start_date'] ?? '';

    if (!$start_date) $errors[] = 'Please choose a start date.';

    $a = $conn->prepare("SELECT extra_price FROM trek_accommodations WHERE id = ? AND trek_id = ?");
    $a->bind_param('ii', $accommodation_id, $booking['trek_id']);
    $a->execute();
    $accom = $a->get_result()->fetch_assoc();
    if (!$accom) $errors[] = 'Invalid accommodation option.';

    $t = $conn->prepare("SELECT extra_price FROM trek_transports WHERE id = ? AND trek_id = ?");
    $t->bind_param('ii', $transport_id, $booking['trek_id']);
    $t->execute();
    $trans = $t->get_result()->fetch_assoc();
    if (!$trans) $errors[] = 'Invalid transport option.';

    if (empty($errors)) {
        $total_price = ((float)$trek['base_price'] + (float)$accom['extra_price'] + (float)$trans['extra_price']) * $group_size;

        $upd = $conn->prepare("UPDATE bookings SET accommodation_id=?, transport_id=?, group_size=?, start_date=?, total_price=? WHERE id=?");
        $upd->bind_param('iiisdi', $accommodation_id, $transport_id, $group_size, $start_date, $total_price, $id);
        $upd->execute();

        header('Location: account.php?updated=1');
        exit;
    }
}

$accommodations = $conn->prepare("
    SELECT ta.id, ta.extra_price, ta.is_default, acc.name, acc.description
    FROM trek_accommodations ta
    JOIN accommodations acc ON acc.id = ta.accommodation_id
    WHERE ta.trek_id = ? ORDER BY ta.is_default DESC, ta.extra_price ASC
");
$accommodations->bind_param('i', $booking['trek_id']);
$accommodations->execute();
$accommodations = $accommodations->get_result();

$transports = $conn->prepare("
    SELECT tt.id, tt.extra_price, tt.is_default, tr.name, tr.description
    FROM trek_transports tt
    JOIN transports tr ON tr.id = tt.transport_id
    WHERE tt.trek_id = ? ORDER BY tt.is_default DESC, tt.extra_price ASC
");
$transports->bind_param('i', $booking['trek_id']);
$transports->execute();
$transports = $transports->get_result();
?>
<?php include 'includes/header.php'; ?>

<div class="detail-wrap" style="max-width:700px;">
    <a href="account.php" class="breadcrumb">&larr; MY ACCOUNT</a>
    <h1 style="font-size:34px;margin:16px 0 6px;">EDIT BOOKING</h1>
    <p style="color:var(--text-muted);text-transform:none;margin-bottom:26px;"><?= h($trek['title']) ?></p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="POST" class="booking-panel" style="position:static;">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" id="basePrice" value="<?= h($trek['base_price']) ?>">
        <input type="hidden" id="selectedAccommodation" name="accommodation_id" value="<?= (int)$booking['accommodation_id'] ?>">
        <input type="hidden" id="selectedTransport" name="transport_id" value="<?= (int)$booking['transport_id'] ?>">
        <input type="hidden" id="selectedGroupSize" name="group_size" value="<?= (int)$booking['group_size'] ?>">

        <div class="panel-eyebrow">UPDATE YOUR TREK</div>
        <div class="panel-total" id="panelTotal">$<?= number_format($booking['total_price']) ?></div>
        <div class="panel-total-label">Total</div>

        <div class="panel-section-title">&#127968; ACCOMMODATION</div>
        <?php while ($a = $accommodations->fetch_assoc()):
            $selected = (int)$a['id'] === (int)$booking['accommodation_id']; ?>
            <div class="option-card <?= $selected ? 'selected' : '' ?>" data-type="accommodation"
                 data-id="<?= (int)$a['id'] ?>" data-extra="<?= h($a['extra_price']) ?>">
                <div class="option-radio"></div>
                <div class="option-info">
                    <div class="option-title-row">
                        <b><?= h($a['name']) ?></b>
                        <span class="price-tag <?= $a['extra_price'] == 0 ? 'included' : '' ?>">
                            <?= $a['extra_price'] == 0 ? 'Included' : '+$' . number_format($a['extra_price']) ?>
                        </span>
                    </div>
                    <div class="option-desc"><?= h($a['description']) ?></div>
                </div>
            </div>
        <?php endwhile; ?>

        <div class="panel-section-title">&#128662; TRANSPORTATION</div>
        <?php while ($t = $transports->fetch_assoc()):
            $selected = (int)$t['id'] === (int)$booking['transport_id']; ?>
            <div class="option-card <?= $selected ? 'selected' : '' ?>" data-type="transport"
                 data-id="<?= (int)$t['id'] ?>" data-extra="<?= h($t['extra_price']) ?>">
                <div class="option-radio"></div>
                <div class="option-info">
                    <div class="option-title-row">
                        <b><?= h($t['name']) ?></b>
                        <span class="price-tag <?= $t['extra_price'] == 0 ? 'included' : '' ?>">
                            <?= $t['extra_price'] == 0 ? 'Included' : ($t['extra_price'] > 0 ? '+$' . number_format($t['extra_price']) : '-$' . number_format(abs($t['extra_price']))) ?>
                        </span>
                    </div>
                    <div class="option-desc"><?= h($t['description']) ?></div>
                </div>
            </div>
        <?php endwhile; ?>

        <div class="panel-section-title">&#128101; GROUP SIZE</div>
        <div class="group-size-row">
            <?php foreach ([1,2,4,6,8,10] as $size): ?>
                <button type="button" class="group-size-btn <?= (int)$booking['group_size'] === $size ? 'selected' : '' ?>" data-size="<?= $size ?>"><?= $size ?></button>
            <?php endforeach; ?>
        </div>

        <div class="panel-section-title">&#128197; START DATE</div>
        <div class="form-field">
            <input type="date" name="start_date" value="<?= h($booking['start_date']) ?>" required min="<?= date('Y-m-d') ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">SAVE CHANGES</button>
        <a href="account.php" class="btn btn-outline btn-block" style="margin-top:10px;">CANCEL</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
