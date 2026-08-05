<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$required = ['company_name', 'contact_name', 'email', 'phone', 'address', 'country', 'state', 'city', 'pincode', 'gst', 'pan', 'password'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $f)) . ' is required'], 400);
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

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO users (role, name, company, country, email, phone, password_hash, status, created_at) VALUES ('seller', ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([trim($input['contact_name']), trim($input['company_name']), trim($input['country']), $email, trim($input['phone']), $passwordHash]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO seller_profiles (user_id, company_name, address_line1, country, state, city, pincode, gst_number, pan_number, website, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        $userId,
        trim($input['company_name']),
        trim($input['address']),
        trim($input['country']),
        trim($input['state']),
        trim($input['city']),
        trim($input['pincode']),
        trim($input['gst']),
        trim($input['pan']),
        trim($input['website'] ?? ''),
        trim($input['about'] ?? '')
    ]);

    $pdo->commit();

    create_notification($userId, 'review', 'Seller registration pending', 'A new seller account is waiting for admin approval.', $userId, 'seller', 'high', ['company' => trim($input['company_name'])]);
    log_activity($userId, 'seller_registered', 'seller', $userId, ['company' => trim($input['company_name']), 'email' => $email]);

    $emailBody = '
    <html><body>
    <h2>Seller Registration Pending</h2>
    <p>Dear ' . e(trim($input['contact_name'])) . ',</p>
    <p>Thank you for registering as a seller on ' . SITE_NAME . '.</p>
    <p>Your account is currently pending approval. We will review your application and get back to you shortly.</p>
    <p><strong>Company:</strong> ' . e(trim($input['company_name'])) . '<br>
    <strong>Email:</strong> ' . e($email) . '</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    send_email($email, 'Seller Registration Pending - ' . SITE_NAME, $emailBody);

    $adminBody = '
    <html><body>
    <h2>New Seller Registration</h2>
    <p>A new seller has registered and is pending approval.</p>
    <p><strong>Company:</strong> ' . e(trim($input['company_name'])) . '<br>
    <strong>Contact:</strong> ' . e(trim($input['contact_name'])) . '<br>
    <strong>Email:</strong> ' . e($email) . '<br>
    <strong>Phone:</strong> ' . e(trim($input['phone'])) . '<br>
    <strong>City:</strong> ' . e(trim($input['city'])) . ', ' . e(trim($input['state'])) . '</p>
    </body></html>';
    send_email(SITE_EMAIL, 'New Seller Registration Pending', $adminBody);

    json_response([
        'success' => true,
        'message' => 'Registration submitted successfully. Your account is pending approval.'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}
