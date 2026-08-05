<?php
require_once __DIR__ . '/../../../../config/config.php';
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
    $where[] = 'bp.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(bp.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR bp.phone LIKE ?)';
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
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
    foreach ($stmtParams as $i => $v) {
        $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $buyers = $stmt->fetchAll();

    json_response([
        'success' => true,
        'data' => [
            'items' => $buyers,
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
