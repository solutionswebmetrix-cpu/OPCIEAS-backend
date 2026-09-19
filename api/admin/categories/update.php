<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../helpers/upload.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
if (!$id) {
    json_response(['success' => false, 'message' => 'Category ID is required'], 400);
}

$fields = [];
$params = [];
$allowedFields = ['name', 'slug', 'description', 'sort_order', 'is_featured', 'status'];
foreach ($allowedFields as $f) {
    if (array_key_exists($f, $input)) {
        $value = $input[$f];
        if ($f === 'status') {
            $value = ((string)$value === 'Draft' || (string)$value === 'inactive') ? 'inactive' : 'active';
        }
        if ($f === 'is_featured') {
            $value = !empty($value) ? 1 : 0;
        }
        if ($f === 'sort_order') {
            $value = (int)$value;
        }
        $fields[] = "$f = ?";
        $params[] = $value;
    }
}

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload = handle_file_upload($_FILES['image'], 'categories');
    if (!$upload['success']) {
        json_response(['success' => false, 'message' => $upload['error']], 400);
    }
    $fields[] = "image = ?";
    $params[] = $upload['path'];
}

if (!$fields) {
    json_response(['success' => false, 'message' => 'No fields to update'], 400);
}

try {
    $params[] = $id;
    $stmt = $pdo->prepare("UPDATE categories SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?");
    $stmt->execute($params);

    log_activity($admin_id, 'category_updated', 'category', $id, $input);

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    json_response(['success' => true, 'message' => 'Category updated successfully', 'data' => $category]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
