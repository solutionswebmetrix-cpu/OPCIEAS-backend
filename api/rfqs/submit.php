<?php
require_once __DIR__ . '/../../config/config.php';

function normalize_rfqs_status($status)
{
    $status = trim((string)($status ?? ''));
    if ($status === '') return 'New';
    $map = [
        'draft' => 'New',
        'submitted' => 'New',
        'under_review' => 'In Review',
        'open' => 'In Review',
        'quotations_received' => 'Quoted',
        'negotiating' => 'In Review',
        'shortlisted' => 'Quoted',
        'awarded' => 'Quoted',
        'closed' => 'Closed',
        'cancelled' => 'Rejected',
        'expired' => 'Closed',
    ];
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function ensure_buyer_for_rfq($pdo, $email, $name, $phone, $company, $country, $city, $state)
{
    $stmt = $pdo->prepare("SELECT u.id, bp.id AS buyer_profile_id FROM users u LEFT JOIN buyer_profiles bp ON bp.user_id = u.id WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row) {
        $userId = (int)($row['id'] ?? 0);
        if (!empty($row['buyer_profile_id'])) {
            return (int)$row['buyer_profile_id'];
        }
    } else {
        $tempPassword = password_hash('temp-' . bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role, name, email, phone, company, country, password_hash, status, created_at) VALUES ('buyer', ?, ?, ?, ?, ?, ?, 'Approved', NOW())");
        $stmt->execute([
            trim($name),
            trim($email),
            trim($phone),
            trim($company),
            trim($country),
            $tempPassword,
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT INTO buyer_profiles (user_id, company_name, city, state, country, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Approved', NOW())");
    $stmt->execute([
        $userId,
        trim($company),
        trim($city),
        trim($state),
        trim($country),
        trim($phone),
    ]);
    return (int)$pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT r.*, bp.company_name, u.name AS contact_name, u.email AS contact_email, u.phone AS contact_phone
            FROM rfqs r
            LEFT JOIN buyer_profiles bp ON bp.id = r.buyer_id
            LEFT JOIN users u ON u.id = bp.user_id
            ORDER BY r.created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (string)($row['id'] ?? ''),
                'company_name' => $row['company_name'] ?? '',
                'contact_name' => $row['contact_name'] ?? $row['contact_person'] ?? '',
                'email' => $row['contact_email'] ?? '',
                'phone' => $row['contact_phone'] ?? '',
                'category' => '',
                'product_name' => $row['product_name'] ?? '',
                'quantity' => (int)($row['quantity'] ?? 0),
                'budget_range_min' => $row['budget_range_min'] !== null ? (float)$row['budget_range_min'] : null,
                'budget_range_max' => $row['budget_range_max'] !== null ? (float)$row['budget_range_max'] : null,
                'required_date' => $row['required_date'] ?? null,
                'description' => $row['description'] ?? '',
                'status' => normalize_rfqs_status($row['status'] ?? ''),
                'is_read' => false,
                'created_at' => $row['created_at'] ?? null,
            ];
        }
        json_response(['success' => true, 'data' => $items]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Failed to load RFQs: ' . $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$hasFileUpload = !empty($input['file_upload_supported']) || !empty($input['has_file_upload']);
$uploadedFile = null;

$required = ['contact_name', 'email', 'product_name', 'quantity'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $f)) . ' is required'], 400);
    }
}

$email = trim($input['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

$contactName = trim($input['contact_name'] ?? $input['contact_person'] ?? '');
$companyName = trim($input['company_name'] ?? $input['company'] ?? '');
$productName = trim($input['product_name'] ?? $input['product'] ?? '');
$quantity = trim((string)($input['quantity'] ?? ''));
$description = trim($input['description'] ?? $input['message'] ?? '');
$expectedDelivery = trim($input['expected_delivery'] ?? $input['required_date'] ?? '');
$phone = trim($input['phone'] ?? $input['contact_phone'] ?? '');
$country = trim($input['country'] ?? '');
$extraSpecifications = [];
foreach (['project_type','sample_requirement','custom_dimensions','frame_colour','modification_requirements','product_sku','specification_notes'] as $key) {
    if (isset($input[$key]) && trim((string)$input[$key]) !== '') {
        $extraSpecifications[ucfirst(str_replace('_', ' ', $key))] = trim((string)$input[$key]);
    }
}
if ($description !== '') {
    $description = preg_replace('/\s+/', ' ', $description);
}
if (!empty($extraSpecifications)) {
    $description .= ($description !== '' ? "\n\n" : '') . "Manufacturing details:\n" . implode("\n", array_map(fn($k, $v) => "$k: $v", array_keys($extraSpecifications), array_values($extraSpecifications)));
}
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$budgetMin = isset($input['budget_range_min']) && $input['budget_range_min'] !== '' ? (float)$input['budget_range_min'] : null;
$budgetMax = isset($input['budget_range_max']) && $input['budget_range_max'] !== '' ? (float)$input['budget_range_max'] : null;
$unit = trim($input['unit'] ?? 'piece');
$unit = $unit !== '' ? $unit : 'piece';
$deliveryLocation = trim($input['delivery_location'] ?? (($city || $country) ? ($city . ($city && $country ? ', ' : '') . $country) : ''));

if ($hasFileUpload && !empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['attachment'];
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        json_response(['success' => false, 'message' => 'File too large (max 10MB)'], 400);
    }
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        json_response(['success' => false, 'message' => 'Invalid file type'], 400);
    }
    if (!is_dir(UPLOAD_DIR . 'rfqs/')) {
        @mkdir(UPLOAD_DIR . 'rfqs/', 0777, true);
    }
    $fname = 'rfq_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . 'rfqs/' . $fname;
    if (@move_uploaded_file($file['tmp_name'], $dest)) {
        $uploadedFile = 'uploads/rfqs/' . $fname;
    }
}

try {
    $buyerId = ensure_buyer_for_rfq($pdo, $email, $contactName, $phone, $companyName, $country, $city, $state);

    $stmt = $pdo->prepare("INSERT INTO rfqs
        (rfq_number, buyer_id, product_name, category_id, quantity, unit, description, specifications, budget_range_min, budget_range_max, required_date, delivery_location, contact_person, contact_email, contact_phone, attachments, status, visibility, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', 'public', NOW())");
    $stmt->execute([
        'temp',
        $buyerId,
        $productName,
        !empty($input['category_id']) ? intval($input['category_id']) : null,
        (int)$quantity,
        $unit,
        $description,
        !empty($extraSpecifications) ? json_encode($extraSpecifications, JSON_UNESCAPED_UNICODE) : null,
        $budgetMin,
        $budgetMax,
        $expectedDelivery !== '' ? $expectedDelivery : null,
        $deliveryLocation !== '' ? $deliveryLocation : null,
        $contactName,
        $email,
        $phone !== '' ? $phone : null,
        $uploadedFile ? json_encode([$uploadedFile]) : null,
    ]);
    $id = (int)$pdo->lastInsertId();
    $rfqNumber = 'RFQ-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE rfqs SET rfq_number = ? WHERE id = ?");
    $stmt->execute([$rfqNumber, $id]);

    create_notification(null, 'rfq', 'New RFQ received', 'A new RFQ request was submitted and is waiting for review.', $id, 'rfq', 'high', [
        'company' => $companyName,
        'product' => $productName,
    ]);
    log_activity($buyerId, 'rfq_submitted', 'rfq', $id, [
        'company' => $companyName,
        'product' => $productName,
        'contact_name' => $contactName,
        'email' => $email,
    ]);

    $fields = '';
    foreach ($input as $k => $v) {
        if ($k === 'password') continue;
        $fields .= '<strong>' . ucfirst(str_replace('_', ' ', $k)) . ':</strong> ' . e($v) . '<br>';
    }
    if ($uploadedFile) {
        $fields .= '<strong>Attachment:</strong> <a href="' . e($uploadedFile) . '">View</a><br>';
    }

    $body = '
    <html><body>
    <h2>New RFQ Submission #' . $id . '</h2>
    <p>A new Request for Quote has been submitted:</p>
    <p>' . $fields . '</p>
    <p>Best regards,<br>' . SITE_NAME . '</p>
    </body></html>';
    try { send_email(SITE_EMAIL, 'New RFQ #' . $id . ' from ' . e($contactName), $body); } catch (Throwable $e) {}

    $userBody = '
    <html><body>
    <h2>Thank you for your RFQ</h2>
    <p>Dear ' . e($contactName) . ',</p>
    <p>We have received your Request for Quote #' . $id . ' for ' . e($productName) . '. Our team will review your requirements and get back to you shortly.</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    try { send_email($email, 'RFQ Received #' . $id . ' - ' . SITE_NAME, $userBody); } catch (Throwable $e) {}

    json_response([
        'success' => true,
        'message' => 'RFQ submitted successfully',
        'id' => $id,
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to submit RFQ: ' . $e->getMessage()], 500);
}
