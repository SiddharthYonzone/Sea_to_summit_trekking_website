<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    // itinerary_days, trek_accommodations, trek_transports, and bookings all
    // cascade-delete automatically via the foreign keys defined in db.sql
    // (the global accommodations/transports catalog entries themselves are
    // untouched, since other treks may still use them)
    $stmt = $conn->prepare("DELETE FROM treks WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

header('Location: treks.php?deleted=1');
exit;
