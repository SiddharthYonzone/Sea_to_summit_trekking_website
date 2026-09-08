<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    // Attaching to treks (trek_accommodations) cascades fine — but if any
    // booking actually references one of those attachments, MySQL's foreign
    // key will block the delete rather than silently orphaning a booking.
    $stmt = $conn->prepare("DELETE FROM accommodations WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();

    if (!$ok) {
        header('Location: accommodations.php?inuse=1');
        exit;
    }
}

header('Location: accommodations.php?deleted=1');
exit;
