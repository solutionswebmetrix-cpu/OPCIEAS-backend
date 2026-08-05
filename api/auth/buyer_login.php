<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(['success' => false, 'message' => 'Email and password are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'buyer' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['success' => false, 'message' => 'Account not found'], 401);
}

if ($user['status'] !== 'Approved') {
    json_response(['success' => false, 'message' => 'Account is not approved yet'], 403);
}

if (!password_verify($password, $user['password_hash'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid password'], 401);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = 'buyer';
$_SESSION['username'] = $user['name'] ?? $user['email'];

$csrf = generate_csrf();

json_response([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'] ?? null,
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null,
        'company' => $user['company'] ?? null,
        'country' => $user['country'] ?? null,
        'role' => 'buyer',
        'status' => $user['status']
    ],
    'csrf_token' => $csrf
]);
