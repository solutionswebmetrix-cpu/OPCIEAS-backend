<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 20);
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($status) {
    $where[] = 'sp.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(sp.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR sp.phone LIKE ?)';
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM seller_profiles sp LEFT JOIN users u ON sp.user_id = u.id $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT sp.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
        sa.full_name AS application_full_name, sa.company_name AS application_company_name, sa.business_registration_number AS application_registration_number,
        sa.tax_identification_number AS application_tax_id, sa.address AS application_address, sa.phone_number AS application_phone_number,
        sa.email AS application_email, sa.whatsapp_number AS application_whatsapp_number, sa.bank_name AS application_bank_name,
        sa.account_number AS application_account_number, sa.ifsc_code AS application_ifsc_code, sa.declaration_text AS application_declaration,
        sa.signature AS application_signature, sa.name_designation AS application_name_designation, sa.application_date AS application_date,
        sa.status AS application_status, sa.review_notes AS application_review_notes
        FROM seller_profiles sp
        LEFT JOIN users u ON sp.user_id = u.id
        LEFT JOIN supplier_applications sa ON sa.user_id = sp.user_id
        $whereSql
        ORDER BY sp.created_at DESC
        LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmtParams = array_merge($params, [$limit, $offset]);
    foreach ($stmtParams as $i => $v) {
        $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $sellers = $stmt->fetchAll();

    json_response([
        'success' => true,
        'data' => [
            'items' => $sellers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
