<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $search = trim((string)($_GET['search'] ?? ''));
    $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim((string)$_GET['status']) : null;

    $where = [];
    $params = [];

    if ($category_id) {
        $where[] = 'p.category_id = ?';
        $params[] = $category_id;
    }

    if ($status) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ? OR p.description LIKE ?)';
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM products p $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            $whereSql
            ORDER BY p.id DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        $row['featured'] = (int)($row['is_featured'] ?? 0);
        $row['is_featured'] = (int)($row['is_featured'] ?? 0);
        $row['price_range'] = $row['price'] !== null ? (string)$row['price'] : '';
        $row['category_name'] = $row['category_name'] ?? '';
        $items[] = $row;
    }

    $pagination = [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => (int)ceil($total / $limit),
        'has_next' => $page < ceil($total / $limit),
        'has_prev' => $page > 1,
    ];

    json_response([
        'success' => true,
        'data' => [
            'items' => $items,
            'pagination' => $pagination,
        ],
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch products: ' . $e->getMessage()], 500);
}
