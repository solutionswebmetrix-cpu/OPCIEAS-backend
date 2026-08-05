<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$stmt = $pdo->prepare("SELECT id, username, email, full_name, role FROM admin_users WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

json_response([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'] ?? 'admin',
        'name' => $user['full_name'] ?? $user['username'],
        'email' => $user['email'] ?? null
    ]
]);
