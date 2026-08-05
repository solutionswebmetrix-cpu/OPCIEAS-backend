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
    $where[] = 'o.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(o.order_number LIKE ? OR bp.company_name LIKE ? OR sp.company_name LIKE ? OR p.name LIKE ?)';
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN buyer_profiles bp ON o.buyer_id = bp.id LEFT JOIN seller_profiles sp ON o.seller_id = sp.id LEFT JOIN products p ON o.product_id = p.id $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT o.*, bp.company_name AS buyer_company, sp.company_name AS seller_company, p.name AS product_name
        FROM orders o
        LEFT JOIN buyer_profiles bp ON o.buyer_id = bp.id
        LEFT JOIN seller_profiles sp ON o.seller_id = sp.id
        LEFT JOIN products p ON o.product_id = p.id
        $whereSql
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmtParams = array_merge($params, [$limit, $offset]);
    foreach ($stmtParams as $i => $v) {
        $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
                'total_pages' => ceil($total / $limit)
            ]
        ]
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
