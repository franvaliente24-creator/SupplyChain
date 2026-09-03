<?php
// core-api/get_user.php — GET /core-api/get_user.php?id=123
// Other modules call this (via db_client.php's core_get_user()) for
// the handful of places they need a username/role for a foreign
// user_id (e.g. stock_requisitions.requested_by, rfps.created_by)
// instead of joining against a local users table that no longer
// exists in their database.

require __DIR__ . '/config.php';
require __DIR__ . '/../core_connection.php';

header('Content-Type: application/json');

$providedKey = $_SERVER['HTTP_X_INTERNAL_KEY'] ?? '';
if (!hash_equals(CORE_INTERNAL_API_KEY, $providedKey)) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'A valid id is required.']);
    exit;
}

$stmt = $conn->prepare('SELECT user_id, username, email, role, is_active FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    echo json_encode($result->fetch_assoc());
} else {
    http_response_code(404);
    echo json_encode(['message' => 'User not found.']);
}

$stmt->close();
$conn->close();