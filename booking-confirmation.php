<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
$code = strtoupper(trim($_GET['code'] ?? ''));
$expectedCode = booking_confirmation_code($id);
if (!$id || !hash_equals($expectedCode, $code)) {
    http_response_code(404);
    header('HTTP/1.1 404 Not Found');
    exit('Booking confirmation not found.');
}

$stmt = $conn->prepare("SELECT b.*, t.title, t.slug, t.base_price, acc.name AS accommodation_name, a.extra_price AS accommodation_extra, trans.name AS transport_name, tr.extra_price AS transport_extra
    FROM bookings b
    JOIN treks t ON t.id = b.trek_id
    JOIN trek_accommodations a ON a.id = b.accommodation_id
    JOIN trek_transports tr ON tr.id = b.transport_id
    JOIN accommodations acc ON acc.id = a.accommodation_id
    JOIN transports trans ON trans.id = tr.transport_id
    WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) {
    http_response_code(404);
    header('HTTP/1.1 404 Not Found');
    exit('Booking confirmation not found.');
}

$groupSize = (int)$booking['group_size'];
$basePerPerson = (float)$booking['base_price'];
$accommodationPerPerson = (float)$booking['accommodation_extra'];
$transportPerPerson = (float)$booking['transport_extra'];
$perPerson = $basePerPerson + $accommodationPerPerson + $transportPerPerson;
$subtotal = $perPerson * $groupSize;
$discountPercent = group_discount_percent($groupSize);
$grandTotal = (float)$booking['total_price'];
$discountAmount = $discountPercent > 0 ? max(0, $subtotal - $grandTotal) : 0;

function confirmation_number_words($number) {
    $number = (int)round($number);
    if ($number === 0) return 'zero';
    $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
    $underThousand = function ($value) use (&$underThousand, $ones, $tens) {
        $words = '';
        if ($value >= 100) {
            $words .= $ones[intdiv($value, 100)] . ' hundred';
            $value %= 100;
            if ($value) $words .= ' and ';
        }
        if ($value >= 20) {
            $words .= $tens[intdiv($value, 10)];
            if ($value % 10) $words .= '-' . $ones[$value % 10];
        } elseif ($value > 0) {
            $words .= $ones[$value];
        }
        return $words;
    };
    $parts = [];
    foreach ([[1000000, 'million'], [1000, 'thousand'], [1, '']] as [$unit, $label]) {
        if ($number >= $unit) {
            $parts[] = $underThousand(intdiv($number, $unit)) . ($label ? ' ' . $label : '');
            $number %= $unit;
        }
    }
    return implode(' ', $parts);
}

$page_title = 'Booking Confirmation';
$active = 'treks';
$base = '';
?>
<?php include 'includes/header.php'; ?>

<main class="confirmation-wrap">
    <div class="confirmation-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">DOWNLOAD PDF / PRINT</button>
        <a href="treks.php" class="btn btn-outline">BROWSE TREKS</a>
    </div>

    <section class="confirmation-sheet">
        <div class="confirmation-kicker">SEA TO SUMMIT TREKKING</div>
        <h1>BOOKING CONFIRMED</h1>
        <p class="confirmation-lead">Thank you, <?= h($booking['full_name']) ?>. Your booking request has been received and our team will contact you shortly.</p>

        <div class="confirmation-code">BOOKING CODE <strong><?= h($expectedCode) ?></strong></div>

        <div class="confirmation-grid">
            <div><span>Guest</span><strong><?= h($booking['full_name']) ?></strong></div>
            <div><span>Email</span><strong><?= h($booking['email']) ?></strong></div>
            <div><span>Trek</span><strong><?= h($booking['title']) ?></strong></div>
            <div><span>Start date</span><strong><?= h(date('F j, Y', strtotime($booking['start_date']))) ?></strong></div>
            <div><span>People</span><strong><?= $groupSize ?></strong></div>
            <div><span>Status</span><strong><?= h($booking['status']) ?></strong></div>
        </div>

        <h2>PRICE BREAKDOWN</h2>
        <div class="confirmation-table-wrap">
            <table class="confirmation-table">
                <thead><tr><th>Item</th><th>Rate / quantity</th><th>Amount</th></tr></thead>
                <tbody>
                    <tr><td><strong>Trek price</strong><small>Guided trek package</small></td><td>$<?= number_format($basePerPerson, 2) ?> x <?= $groupSize ?></td><td>$<?= number_format($basePerPerson * $groupSize, 2) ?></td></tr>
                    <tr><td><strong>Accommodation</strong><small><?= h($booking['accommodation_name']) ?></small></td><td>$<?= number_format($accommodationPerPerson, 2) ?> x <?= $groupSize ?></td><td>$<?= number_format($accommodationPerPerson * $groupSize, 2) ?></td></tr>
                    <tr><td><strong>Transportation</strong><small><?= h($booking['transport_name']) ?></small></td><td>$<?= number_format($transportPerPerson, 2) ?> x <?= $groupSize ?></td><td>$<?= number_format($transportPerPerson * $groupSize, 2) ?></td></tr>
                    <tr class="confirmation-subtotal"><td colspan="2">Subtotal</td><td>$<?= number_format($subtotal, 2) ?></td></tr>
                    <?php if ($discountAmount > 0): ?><tr class="confirmation-discount"><td colspan="2">Group discount</td><td>-$<?= number_format($discountAmount, 2) ?></td></tr><?php endif; ?>
                    <tr class="confirmation-grand-total"><td colspan="2">Grand total</td><td>$<?= number_format($grandTotal, 2) ?></td></tr>
                </tbody>
            </table>
        </div>

        <p class="confirmation-words">Grand total in words: <strong><?= h(ucwords(confirmation_number_words($grandTotal))) ?> US dollars only.</strong></p>
        <p class="confirmation-note">This is a booking request. The status is currently <?= h(strtolower($booking['status'])) ?>. Our team will confirm availability and follow up using the contact details above.</p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>