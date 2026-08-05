<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT id, name, slug, description, image AS image_url, parent_id, sort_order, status FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    $categories = $stmt->fetchAll();

    json_response([
        'success' => true,
        'count' => count($categories),
        'data' => $categories
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch categories: ' . $e->getMessage()], 500);
}
