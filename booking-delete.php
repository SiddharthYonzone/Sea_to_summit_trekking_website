<?php
require_once 'config.php';

if (!is_customer_logged_in()) {
    header('Location: account-login.php');
    exit;
}

$customer = current_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    // Only delete if this booking actually belongs to the logged-in customer
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND (customer_id = ? OR email = ?)");
    $stmt->bind_param('iis', $id, $customer['id'], $customer['email']);
    $stmt->execute();
}

header('Location: account.php?deleted=1');
exit;
