<?php
// core-api/log.php — POST endpoint other modules will call (via
// db_client.php's core_log()) once they're on their own databases,
// instead of doing INSERT INTO activity_log directly.
//
// Not wired into anything yet. This just gets the endpoint ready
// for when you migrate the first non-core module.

require __DIR__ . '/config.php';
require __DIR__ . '/../core_connection.php';

header('Content-Type: application/json');

$providedKey = $_SERVER['HTTP_X_INTERNAL_KEY'] ?? '';
if (!hash_equals(CORE_INTERNAL_API_KEY, $providedKey)) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'POST only.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$user_id      = isset($data['user_id']) ? (int)$data['user_id'] : null;
$username     = $data['username']   ?? null;
$action       = $data['action']     ?? null;
$details      = $data['details']    ?? null;
$ip_address   = $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
$user_agent   = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
$label        = $data['label']        ?? null;
$status       = $data['status']       ?? null;
$status_class = $data['status_class'] ?? null;

// Every call needs at least one of the two logging "shapes" your
// codebase currently uses: the audit-trail shape (action) or the
// dashboard-widget shape (label).
if (!$action && !$label) {
    http_response_code(400);
    echo json_encode(['message' => 'Either action or label is required.']);
    exit;
}

$types = 'i' . str_repeat('s', 8);
$stmt = $conn->prepare(
    "INSERT INTO activity_log
        (user_id, username, action, details, ip_address, user_agent, label, status, status_class)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param($types, $user_id, $username, $action, $details, $ip_address, $user_agent, $label, $status, $status_class);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

if ($ok) {
    echo json_encode(['message' => 'Logged.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to log.']);
}