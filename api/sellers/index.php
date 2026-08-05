<?php
require_once __DIR__ . '/../../config/config.php';
$admin_id = require_role('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    $search = trim((string)($_GET['search'] ?? ''));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($status) {
        $where[] = 'sp.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(sp.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR sp.phone LIKE ?)';
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM seller_profiles sp LEFT JOIN users u ON sp.user_id = u.id $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT sp.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM seller_profiles sp
        LEFT JOIN users u ON sp.user_id = u.id
        $whereSql
        ORDER BY sp.created_at DESC
        LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmtParams = array_merge($params, [$limit, $offset]);
    for ($i = 0; $i < count($stmtParams); $i++) {
        $stmt->bindValue($i + 1, $stmtParams[$i], is_int($stmtParams[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $items = $stmt->fetchAll();

    json_response([
        'success' => true,
        'data' => [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int)ceil($total / max(1, $limit)),
            ],
        ],
    ]);
}

if ($method === 'POST') {
    $input = get_input();
    $required = ['company_name', 'contact_name', 'email', 'phone', 'country', 'state', 'city', 'pincode', 'password'];
    foreach ($required as $field) {
        if (empty(trim((string)($input[$field] ?? '')))) {
            json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
        }
    }

    $email = trim((string)($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    $companyName = trim((string)($input['company_name'] ?? ''));
    $contactName = trim((string)($input['contact_name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $country = trim((string)($input['country'] ?? ''));
    $state = trim((string)($input['state'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $pincode = trim((string)($input['pincode'] ?? ''));
    $address = trim((string)($input['address'] ?? '')) ?: trim($city . ', ' . $state);
    $gst = trim((string)($input['gst'] ?? ''));
    $pan = trim((string)($input['pan'] ?? ''));
    $website = trim((string)($input['website'] ?? ''));
    $about = trim((string)($input['about'] ?? ''));
    $passwordHash = password_hash((string)($input['password'] ?? ''), PASSWORD_DEFAULT);

    $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $emailStmt->execute([$email]);
    if ($emailStmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already exists.'], 409);
    }

    if ($companyName !== '') {
        $companyStmt = $pdo->prepare('SELECT id FROM seller_profiles WHERE LOWER(TRIM(company_name)) = LOWER(TRIM(?)) LIMIT 1');
        $companyStmt->execute([$companyName]);
        if ($companyStmt->fetch()) {
            json_response(['success' => false, 'message' => 'Company already registered.'], 409);
        }
    }

    if ($gst !== '') {
        $gstStmt = $pdo->prepare('SELECT id FROM seller_profiles WHERE gst_number IS NOT NULL AND TRIM(gst_number) <> "" AND LOWER(TRIM(gst_number)) = LOWER(TRIM(?)) LIMIT 1');
        $gstStmt->execute([$gst]);
        if ($gstStmt->fetch()) {
            json_response(['success' => false, 'message' => 'GST Number already exists.'], 409);
        }
    }

    if ($pan !== '') {
        $panStmt = $pdo->prepare('SELECT id FROM seller_profiles WHERE pan_number IS NOT NULL AND TRIM(pan_number) <> "" AND LOWER(TRIM(pan_number)) = LOWER(TRIM(?)) LIMIT 1');
        $panStmt->execute([$pan]);
        if ($panStmt->fetch()) {
            json_response(['success' => false, 'message' => 'PAN Number already exists.'], 409);
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (role, name, company, country, email, phone, password_hash, status, created_at) VALUES ('seller', ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->execute([$contactName, $companyName, $country, $email, $phone, $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO seller_profiles (user_id, company_name, address_line1, country, state, city, pincode, gst_number, pan_number, website, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->execute([
            $userId,
            $companyName,
            $address,
            $country,
            $state,
            $city,
            $pincode,
            $gst,
            $pan,
            $website,
            $about,
        ]);
        $sellerProfileId = (int)$pdo->lastInsertId();

        $pdo->commit();

        log_activity($admin_id, 'seller_created', 'seller', $sellerProfileId, [
            'company_name' => $companyName,
            'contact_name' => $contactName,
            'email' => $email,
            'status' => 'Pending',
        ]);
        create_notification($userId, 'review', 'Seller registration pending', 'A new seller account is waiting for admin approval.', $sellerProfileId, 'seller', 'high', ['company' => $companyName]);

        json_response([
            'success' => true,
            'message' => 'Seller created successfully.',
            'seller' => [
                'id' => $sellerProfileId,
                'user_id' => $userId,
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'email' => $email,
                'status' => 'Pending',
            ],
            'data' => [
                'id' => $sellerProfileId,
                'user_id' => $userId,
            ],
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e->getMessage();
        $errorMessage = 'Seller creation failed.';

        if (str_contains($message, 'users_email_unique') || str_contains($message, 'email')) {
            $errorMessage = 'Email already exists.';
        } elseif (str_contains($message, 'seller_profiles_company_name_unique') || str_contains($message, 'company_name')) {
            $errorMessage = 'Company already registered.';
        } elseif (str_contains($message, 'seller_profiles_gst_number_unique') || str_contains($message, 'gst_number')) {
            $errorMessage = 'GST Number already exists.';
        } elseif (str_contains($message, 'seller_profiles_pan_number_unique') || str_contains($message, 'pan_number')) {
            $errorMessage = 'PAN Number already exists.';
        }

        json_response(['success' => false, 'message' => $errorMessage], 409);
    }
}

if ($method === 'PUT') {
    $input = get_input();
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        json_response(['success' => false, 'message' => 'Seller ID is required'], 400);
    }

    try {
        $pdo->beginTransaction();
        $allowed = ['company_name', 'business_type', 'phone', 'website', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode', 'gst_number', 'pan_number', 'registration_number', 'description', 'verification_status', 'verification_remarks', 'status'];
        $sets = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = $field . ' = ?';
                $params[] = $input[$field];
            }
        }
        if (!$sets) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'No seller fields to update'], 400);
        }
        $sets[] = 'updated_at = NOW()';
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE seller_profiles SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Seller updated successfully']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'Seller update failed: ' . $e->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    $input = get_input();
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        json_response(['success' => false, 'message' => 'Seller ID is required'], 400);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM seller_profiles WHERE id = ?');
        $stmt->execute([$id]);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Seller deleted successfully']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'Seller deletion failed: ' . $e->getMessage()], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
