<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$required = ['name', 'email', 'phone', 'country', 'password'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst($f) . ' is required'], 400);
    }
}

$email = trim($input['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'Email already registered'], 400);
}

$passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
$name = trim($input['name']);
$phone = trim($input['phone']);
$company = trim($input['company'] ?? '');
$country = trim($input['country']);
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (role, email, phone, name, company, country, password_hash, status, created_at) VALUES ('buyer', ?, ?, ?, ?, ?, ?, 'Approved', NOW())");
    $stmt->execute([$email, $phone, $name, $company, $country, $passwordHash]);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO buyer_profiles (user_id, company_name, city, state, country, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Approved', NOW())");
    $stmt->execute([$userId, $company, $city, $state, $country, $phone]);

    $pdo->commit();

    create_notification($userId, 'system', 'Buyer account created', 'Your buyer account is now active and ready to use.', $userId, 'buyer', 'normal', ['email' => $email]);
    log_activity($userId, 'buyer_registered', 'buyer', $userId, ['email' => $email, 'company' => $company]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'buyer';
    $_SESSION['username'] = $name;

    $csrf = generate_csrf();

    json_response([
        'success' => true,
        'message' => 'Registration and login successful',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'country' => $country,
            'role' => 'buyer',
            'status' => 'Approved'
        ],
        'csrf_token' => $csrf
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}
