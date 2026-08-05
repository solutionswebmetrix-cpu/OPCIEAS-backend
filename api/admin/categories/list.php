<?php
require_once __DIR__ . '/../../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT id, parent_id, name, slug, description, image, icon, sort_order, is_featured, status, meta_title, meta_description, created_at, updated_at FROM categories ORDER BY sort_order ASC, name ASC");
    $categories = $stmt->fetchAll();

    json_response([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch categories: ' . $e->getMessage()], 500);
}
