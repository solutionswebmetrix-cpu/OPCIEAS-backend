<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
if (!$id) {
    json_response(['success' => false, 'message' => 'Category ID is required'], 400);
}

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $countStmt->execute([$id]);
    $productCount = (int)$countStmt->fetchColumn();
    if ($productCount > 0) {
        json_response([
            'success' => false,
            'message' => "Cannot delete this category because it contains {$productCount} products. Reassign the products first."
        ], 409);
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    log_activity($admin_id, 'category_deleted', 'category', $id, ['id' => $id]);

    json_response(['success' => true, 'message' => 'Category deleted successfully']);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
