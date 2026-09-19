<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$fullName = trim($input['full_name'] ?? $input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone_number'] ?? $input['phone'] ?? '');
$company = trim($input['company_name'] ?? $input['company'] ?? '');
$country = trim($input['country'] ?? 'India');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$address = trim($input['address'] ?? $input['address_line1'] ?? '');
$businessRegistrationNumber = trim($input['business_registration_number'] ?? $input['gst_number'] ?? '');
$whatsappNumber = trim($input['whatsapp_number'] ?? '');
$businessPurpose = trim($input['business_purpose'] ?? '');
$preferredCategories = $input['preferred_categories'] ?? [];
if (is_string($preferredCategories)) {
    $preferredCategories = array_values(array_filter(array_map('trim', explode(',', $preferredCategories))));
} elseif (!is_array($preferredCategories)) {
    $preferredCategories = [];
}
$preferredCategoriesJson = json_encode(array_values(array_unique($preferredCategories)), JSON_UNESCAPED_UNICODE);
$declarationText = trim($input['declaration_text'] ?? 'I/We hereby declare that my/our registration is genuine and for authentic business purposes, in compliance with OPCIEAS norms.');
$signature = trim($input['signature'] ?? '');
$nameDesignation = trim($input['name_designation'] ?? '');
$applicationDate = trim($input['application_date'] ?? '');
$password = $input['password'] ?? '';

$required = ['full_name' => $fullName, 'email' => $email, 'phone_number' => $phone, 'address' => $address, 'business_purpose' => $businessPurpose, 'declaration_text' => $declarationText, 'signature' => $signature, 'name_designation' => $nameDesignation, 'application_date' => $applicationDate, 'password' => $password];
foreach ($required as $field => $value) {
    if (is_string($value) && trim($value) === '') {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

if (!preg_match('/^\+?[0-9\s\-()]{7,15}$/', $phone)) {
    json_response(['success' => false, 'message' => 'Invalid phone number'], 400);
}

if (!preg_match('/^\+?[0-9\s\-()]{7,15}$/', $whatsappNumber)) {
    json_response(['success' => false, 'message' => 'Invalid WhatsApp number'], 400);
}

if (strlen($password) < 8) {
    json_response(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'Email already registered'], 400);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (role, email, phone, name, company, country, password_hash, status, created_at) VALUES ('buyer', ?, ?, ?, ?, ?, ?, 'Approved', NOW())");
    $stmt->execute([$email, $phone, $fullName, $company, $country, $passwordHash]);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO buyer_profiles (user_id, company_name, city, state, country, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Approved', NOW())");
    $stmt->execute([$userId, $company, $city, $state, $country, $phone]);

    $stmt = $pdo->prepare("INSERT INTO buyer_applications (user_id, full_name, company_name, business_registration_number, address, phone_number, email, whatsapp_number, business_purpose, preferred_categories, declaration_text, signature, name_designation, application_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([$userId, $fullName, $company, $businessRegistrationNumber, $address, $phone, $email, $whatsappNumber, $businessPurpose, $preferredCategoriesJson, $declarationText, $signature, $nameDesignation, $applicationDate !== '' ? $applicationDate : null]);

    $pdo->commit();

    create_notification($userId, 'system', 'Buyer application received', 'Your buyer application has been received and is under review.', $userId, 'buyer', 'normal', ['email' => $email]);
    log_activity($userId, 'buyer_application_submitted', 'buyer', $userId, ['email' => $email, 'company' => $company]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'buyer';
    $_SESSION['username'] = $fullName;

    $csrf = generate_csrf();

    json_response([
        'success' => true,
        'message' => 'Buyer application submitted successfully. Your account is ready for review.',
        'user' => [
            'id' => $userId,
            'name' => $fullName,
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
