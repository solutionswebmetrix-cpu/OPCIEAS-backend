<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$loginValue = $username !== '' ? $username : $email;
$password = $input['password'] ?? '';
$remember = !empty($input['remember']);

if (empty($loginValue) || empty($password)) {
    json_response(['success' => false, 'message' => 'Invalid email or password.'], 400);
}

$seedStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
$seedStmt->execute();
if ((int)$seedStmt->fetchColumn() === 0) {
    $seedHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
    $seedInsert = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $seedInsert->execute(['admin', 'admin@opcieas.com', $seedHash, 'System Administrator', 'super_admin']);
}

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$loginValue, $loginValue]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'] ?? $user['password'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'] ?? 'admin';
$_SESSION['username'] = $user['username'];
$_SESSION['authenticated'] = true;

$token = null;
if ($remember) {
    $token = generate_remember_token();
    $stmt = $pdo->prepare("UPDATE admin_users SET remember_token = ?, remember_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
    $stmt->execute([$token, $user['id']]);
    setcookie('admin_remember', $token, time() + (86400 * 30), '/', '', false, true);
}

$csrf = generate_csrf();

json_response([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'] ?? 'admin',
        'name' => $user['full_name'] ?? $user['name'] ?? $user['username'],
        'email' => $user['email'] ?? null
    ],
    'token' => $token,
    'remember_token' => $token,
    'csrf_token' => $csrf,
    'redirect' => '/dashboard'
]);
