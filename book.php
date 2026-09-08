<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: treks.php');
    exit;
}

$trek_id          = (int)($_POST['trek_id'] ?? 0);
$accommodation_id = (int)($_POST['accommodation_id'] ?? 0);
$transport_id     = (int)($_POST['transport_id'] ?? 0);
$group_size       = max(1, (int)($_POST['group_size'] ?? 1));
$start_date       = $_POST['start_date'] ?? '';

$customer_id = null;
if (is_customer_logged_in()) {
    // Trust the logged-in account's own details rather than posted form fields
    $customer   = current_customer();
    $customer_id = $customer['id'];
    $full_name  = $customer['full_name'];
    $email      = $customer['email'];
    $phone      = $customer['phone'] ?: trim($_POST['phone'] ?? '');
} else {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
}

// Basic validation
if (!$trek_id || !$accommodation_id || !$transport_id || !$start_date || !$full_name || !$email || !$phone) {
    die('Missing required fields. <a href="javascript:history.back()">Go back</a>');
}

// Look up trek + selected options to calculate the true price server-side
$stmt = $conn->prepare("SELECT slug, base_price FROM treks WHERE id = ?");
$stmt->bind_param('i', $trek_id);
$stmt->execute();
$trek = $stmt->get_result()->fetch_assoc();
if (!$trek) die('Invalid trek.');

$stmt = $conn->prepare("SELECT extra_price FROM trek_accommodations WHERE id = ? AND trek_id = ?");
$stmt->bind_param('ii', $accommodation_id, $trek_id);
$stmt->execute();
$accom = $stmt->get_result()->fetch_assoc();
if (!$accom) die('Invalid accommodation option.');

$stmt = $conn->prepare("SELECT extra_price FROM trek_transports WHERE id = ? AND trek_id = ?");
$stmt->bind_param('ii', $transport_id, $trek_id);
$stmt->execute();
$trans = $stmt->get_result()->fetch_assoc();
if (!$trans) die('Invalid transport option.');

$total_price = ((float)$trek['base_price'] + (float)$accom['extra_price'] + (float)$trans['extra_price']) * $group_size;

$stmt = $conn->prepare("INSERT INTO bookings
    (trek_id, accommodation_id, transport_id, customer_id, full_name, email, phone, group_size, start_date, total_price, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
$stmt->bind_param('iiiisssisd',
    $trek_id, $accommodation_id, $transport_id, $customer_id, $full_name, $email, $phone, $group_size, $start_date, $total_price);
$stmt->execute();

header('Location: trek-detail.php?slug=' . urlencode($trek['slug']) . '&booked=1');
exit;
