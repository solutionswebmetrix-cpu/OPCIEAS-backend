<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$fullName = trim($input['full_name'] ?? $input['name'] ?? $input['contact_name'] ?? '');
$companyName = trim($input['company_name'] ?? '');
$businessRegistrationNumber = trim($input['business_registration_number'] ?? $input['gst'] ?? $input['registration_number'] ?? '');
$taxIdentificationNumber = trim($input['tax_identification_number'] ?? $input['pan'] ?? '');
$address = trim($input['address'] ?? $input['address_line1'] ?? '');
$phoneNumber = trim($input['phone_number'] ?? $input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$whatsappNumber = trim($input['whatsapp_number'] ?? '');
$bankName = trim($input['bank_name'] ?? '');
$accountNumber = trim($input['account_number'] ?? '');
$ifscCode = trim($input['ifsc_code'] ?? '');
$declarationText = trim($input['declaration_text'] ?? 'I/We hereby declare that only standard products, compliant with OPCIEAS norms, will be supplied through this platform.');
$signature = trim($input['signature'] ?? '');
$nameDesignation = trim($input['name_designation'] ?? '');
$applicationDate = trim($input['application_date'] ?? '');
$password = $input['password'] ?? '';

$requiredFields = [
    'full_name' => $fullName,
    'company_name' => $companyName,
    'business_registration_number' => $businessRegistrationNumber,
    'tax_identification_number' => $taxIdentificationNumber,
    'address' => $address,
    'phone_number' => $phoneNumber,
    'email' => $email,
    'whatsapp_number' => $whatsappNumber,
    'bank_name' => $bankName,
    'account_number' => $accountNumber,
    'ifsc_code' => $ifscCode,
    'declaration_text' => $declarationText,
    'signature' => $signature,
    'name_designation' => $nameDesignation,
    'application_date' => $applicationDate,
    'password' => $password,
];
foreach ($requiredFields as $field => $value) {
    if (is_string($value) && trim($value) === '') {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

if (!preg_match('/^\+?[0-9\s\-()]{7,15}$/', $phoneNumber)) {
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

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO users (role, name, company, country, email, phone, password_hash, status, created_at) VALUES ('seller', ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([$fullName, $companyName, trim($input['country'] ?? 'India'), $email, $phoneNumber, $passwordHash]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO seller_profiles (user_id, company_name, address_line1, country, state, city, pincode, gst_number, pan_number, website, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        $userId,
        $companyName,
        $address,
        trim($input['country'] ?? 'India'),
        trim($input['state'] ?? ''),
        trim($input['city'] ?? ''),
        trim($input['pincode'] ?? ''),
        $businessRegistrationNumber,
        $taxIdentificationNumber,
        trim($input['website'] ?? ''),
        trim($input['about'] ?? '')
    ]);

    $stmt = $pdo->prepare("INSERT INTO supplier_applications (user_id, full_name, company_name, business_registration_number, tax_identification_number, address, phone_number, email, whatsapp_number, bank_name, account_number, ifsc_code, declaration_text, signature, name_designation, application_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        $userId,
        $fullName,
        $companyName,
        $businessRegistrationNumber,
        $taxIdentificationNumber,
        $address,
        $phoneNumber,
        $email,
        $whatsappNumber,
        $bankName,
        $accountNumber,
        $ifscCode,
        $declarationText,
        $signature,
        $nameDesignation,
        $applicationDate !== '' ? $applicationDate : null,
    ]);

    $pdo->commit();

    create_notification($userId, 'review', 'Supplier application pending', 'A new supplier application is waiting for admin approval.', $userId, 'seller', 'high', ['company' => $companyName]);
    log_activity($userId, 'supplier_application_submitted', 'seller', $userId, ['company' => $companyName, 'email' => $email]);

    $emailBody = '
    <html><body>
    <h2>Supplier Application Pending</h2>
    <p>Dear ' . e($fullName) . ',</p>
    <p>Thank you for submitting your supplier application to ' . SITE_NAME . '.</p>
    <p>Your application is currently pending review by our portal staff. We will contact you if any further details are required.</p>
    <p><strong>Company:</strong> ' . e($companyName) . '<br>
    <strong>Email:</strong> ' . e($email) . '</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    send_email($email, 'Supplier Application Pending - ' . SITE_NAME, $emailBody);

    $adminBody = '
    <html><body>
    <h2>New Supplier Application</h2>
    <p>A new supplier application has been submitted and is awaiting review.</p>
    <p><strong>Company:</strong> ' . e($companyName) . '<br>
    <strong>Contact:</strong> ' . e($fullName) . '<br>
    <strong>Email:</strong> ' . e($email) . '<br>
    <strong>Phone:</strong> ' . e($phoneNumber) . '</p>
    </body></html>';
    send_email(SITE_EMAIL, 'New Supplier Application Pending', $adminBody);

    json_response([
        'success' => true,
        'message' => 'Supplier application submitted successfully. Your account is pending approval.'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}
