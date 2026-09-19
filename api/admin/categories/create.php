<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../helpers/upload.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$name = trim((string)($input['name'] ?? ''));
$slug = trim((string)($input['slug'] ?? ''));
if ($slug === '') {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
}
if ($name === '') {
    json_response(['success' => false, 'message' => 'Name is required'], 400);
}

$sort_order = isset($input['sort_order']) ? (int)$input['sort_order'] : 0;
$is_featured = !empty($input['is_featured']) ? 1 : 0;
$status = !empty($input['status']) ? ((string)$input['status'] === 'Draft' ? 'inactive' : 'active') : 'active';
$description = $input['description'] ?? null;
$imagePath = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload = handle_file_upload($_FILES['image'], 'categories');
    if (!$upload['success']) {
        json_response(['success' => false, 'message' => $upload['error']], 400);
    }
    $imagePath = $upload['path'];
}

try {
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image, sort_order, is_featured, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$name, $slug, $description, $imagePath, $sort_order, $is_featured, $status]);
    $id = (int)$pdo->lastInsertId();

    log_activity($admin_id, 'category_created', 'category', $id, ['name' => $name, 'slug' => $slug, 'image' => $imagePath]);

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    json_response(['success' => true, 'message' => 'Category created successfully', 'data' => $category]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
