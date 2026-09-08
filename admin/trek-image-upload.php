<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be signed in as an admin.']);
    exit;
}

$error = '';
$path = process_image_upload($_FILES['image'] ?? [], $error);
if (!$path) {
    http_response_code(400);
    echo json_encode(['error' => $error ?: 'Image upload failed.']);
    exit;
}

echo json_encode(['url' => '../' . $path]);