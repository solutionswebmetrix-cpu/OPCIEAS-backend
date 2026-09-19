<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$required = ['full_name','company_name','business_registration_number','tax_identification_number','address','phone_number','email','whatsapp_number','bank_name','account_number','ifsc_code','declaration_text','signature','name_designation','application_date'];
foreach ($required as $field) {
    if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
    }
}

$email = trim((string)($input['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

if (!preg_match('/^\+?[0-9\s\-()]{7,15}$/', trim((string)($input['phone_number'] ?? '')))) {
    json_response(['success' => false, 'message' => 'Invalid phone number'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO supplier_applications (full_name, company_name, business_registration_number, tax_identification_number, address, phone_number, email, whatsapp_number, bank_name, account_number, ifsc_code, declaration_text, signature, name_designation, application_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        trim((string)$input['full_name']),
        trim((string)$input['company_name']),
        trim((string)$input['business_registration_number']),
        trim((string)$input['tax_identification_number']),
        trim((string)$input['address']),
        trim((string)$input['phone_number']),
        $email,
        trim((string)$input['whatsapp_number']),
        trim((string)$input['bank_name']),
        trim((string)$input['account_number']),
        trim((string)$input['ifsc_code']),
        trim((string)$input['declaration_text']),
        trim((string)$input['signature']),
        trim((string)$input['name_designation']),
        trim((string)$input['application_date']),
    ]);

    json_response(['success' => true, 'message' => 'Supplier application submitted successfully']);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
