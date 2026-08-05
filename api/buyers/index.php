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
        $where[] = 'bp.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(bp.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR bp.phone LIKE ?)';
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM buyer_profiles bp LEFT JOIN users u ON bp.user_id = u.id $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT bp.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM buyer_profiles bp
        LEFT JOIN users u ON bp.user_id = u.id
        $whereSql
        ORDER BY bp.created_at DESC
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
    $required = ['name', 'email', 'phone', 'country', 'password'];
    foreach ($required as $field) {
        if (empty(trim((string)($input[$field] ?? '')))) {
            json_response(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
        }
    }

    $email = trim((string)($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already registered'], 409);
    }

    $name = trim((string)($input['name'] ?? ''));
    $company = trim((string)($input['company'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $country = trim((string)($input['country'] ?? ''));
    $state = trim((string)($input['state'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $passwordHash = password_hash((string)($input['password'] ?? ''), PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (role, email, phone, name, company, country, password_hash, status, created_at) VALUES ('buyer', ?, ?, ?, ?, ?, ?, 'Approved', NOW())");
        $stmt->execute([$email, $phone, $name, $company, $country, $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO buyer_profiles (user_id, company_name, city, state, country, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Approved', NOW())");
        $stmt->execute([$userId, $company ?: $name, $city, $state, $country, $phone]);
        $buyerProfileId = (int)$pdo->lastInsertId();

        $pdo->commit();

        log_activity($admin_id, 'buyer_created', 'buyer', $buyerProfileId, [
            'company_name' => $company ?: $name,
            'name' => $name,
            'email' => $email,
            'status' => 'Approved',
        ]);
        create_notification($userId, 'system', 'Buyer account created', 'A new buyer account was created by the admin panel.', $buyerProfileId, 'buyer', 'normal', ['email' => $email]);

        json_response([
            'success' => true,
            'message' => 'Buyer created successfully',
            'data' => [
                'id' => $buyerProfileId,
                'user_id' => $userId,
            ],
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'Buyer creation failed: ' . $e->getMessage()], 500);
    }
}

if ($method === 'PUT') {
    $input = get_input();
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        json_response(['success' => false, 'message' => 'Buyer ID is required'], 400);
    }

    try {
        $pdo->beginTransaction();
        $allowed = ['company_name', 'business_type', 'phone', 'website', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode', 'gst_number', 'status'];
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
            json_response(['success' => false, 'message' => 'No buyer fields to update'], 400);
        }
        $sets[] = 'updated_at = NOW()';
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE buyer_profiles SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Buyer updated successfully']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'Buyer update failed: ' . $e->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    $input = get_input();
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        json_response(['success' => false, 'message' => 'Buyer ID is required'], 400);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM buyer_profiles WHERE id = ?');
        $stmt->execute([$id]);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Buyer deleted successfully']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => 'Buyer deletion failed: ' . $e->getMessage()], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
