<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 20);
$offset = ($page - 1) * $limit;
$only_unread = isset($_GET['unread']) && ($_GET['unread'] === '1' || $_GET['unread'] === 'true');

try {
    $where = [];
    $params = [];

    if ($only_unread) {
        $where[] = 'is_read = 0';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $unreadStmt->execute();
    $unread_count = (int)$unreadStmt->fetchColumn();

    $sql = "SELECT * FROM notifications $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
            'unread_count' => $unread_count,
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
