<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$required = ['name', 'category_id'];
foreach ($required as $r) {
    if (empty($input[$r])) {
        json_response(['success' => false, 'message' => "$r is required"], 400);
    }
}

$name = trim((string)($input['name'] ?? ''));
$slug = !empty($input['slug']) ? trim((string)$input['slug']) : strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . substr(uniqid(), 0, 6);
$category_id = (int)($input['category_id'] ?? 0);
$price = isset($input['price']) ? (float)$input['price'] : ((isset($input['price_range']) && is_numeric($input['price_range'])) ? (float)$input['price_range'] : 0);
$discount_price = isset($input['discount_price']) ? (float)$input['discount_price'] : null;
$stock_quantity = isset($input['stock_quantity']) ? (int)$input['stock_quantity'] : 0;
$short_description = $input['short_description'] ?? ($input['short_desc'] ?? null);
$description = $input['description'] ?? ($input['long_desc'] ?? null);
$features = !empty($input['features']) ? $input['features'] : (!empty($input['features_json']) ? $input['features_json'] : []);
$specifications = !empty($input['specs']) ? $input['specs'] : (!empty($input['specifications']) ? $input['specifications'] : (!empty($input['specs_json']) ? $input['specs_json'] : null));
$is_featured = isset($input['featured']) ? (int)$input['featured'] : (isset($input['is_featured']) ? (int)$input['is_featured'] : 0);
$status = $input['status'] ?? 'Pending';
if ($status === 'Draft') {
    $status = 'Hidden';
} elseif ($status === 'Published') {
    $status = 'Published';
} elseif ($status === 'Rejected') {
    $status = 'Rejected';
} else {
    $status = 'Pending';
}
$seller_id = !empty($input['seller_id']) ? (int)$input['seller_id'] : null;
if (!$seller_id) {
    $sellerStmt = $pdo->prepare("SELECT id FROM seller_profiles ORDER BY id LIMIT 1");
    $sellerStmt->execute();
    $seller_id = (int)$sellerStmt->fetchColumn();
}
if (!$seller_id) {
    json_response(['success' => false, 'message' => 'No seller profile available for this product'], 400);
}

$validStatuses = ['Pending', 'Published', 'Hidden', 'Rejected'];
if (!in_array($status, $validStatuses, true)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, name, slug, sku, short_description, description, features, specifications, price, discount_price, stock_quantity, is_featured, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $seller_id,
        $category_id,
        $name,
        $slug,
        $input['sku'] ?? null,
        $short_description,
        $description,
        is_array($features) ? json_encode($features) : null,
        is_array($specifications) ? json_encode($specifications) : ($specifications ? (string)$specifications : null),
        $price,
        $discount_price,
        $stock_quantity,
        $is_featured,
        $status
    ]);
    $product_id = (int)$pdo->lastInsertId();

    $image_urls = [];
    $filesToProcess = [];
    if (isset($_FILES['image_uploads']) && is_array($_FILES['image_uploads']['tmp_name'])) {
        $count = count($_FILES['image_uploads']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['image_uploads']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $filesToProcess[] = [
                'name' => $_FILES['image_uploads']['name'][$i],
                'type' => $_FILES['image_uploads']['type'][$i],
                'tmp_name' => $_FILES['image_uploads']['tmp_name'][$i],
                'error' => $_FILES['image_uploads']['error'][$i],
                'size' => $_FILES['image_uploads']['size'][$i]
            ];
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $filesToProcess[] = $_FILES['image'];
    }

    foreach ($filesToProcess as $i => $file) {
        $result = upload_file($file, 'products');
        if ($result['success']) {
            $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_url, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
            $imgStmt->execute([$product_id, $result['path'] ?? $result['url'], $result['url'] ?? '', $i]);
            $image_urls[] = $result['url'];
        }
    }

    $productStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $productStmt->execute([$product_id]);
    $createdProduct = $productStmt->fetch();

    log_activity($admin_id, 'product_created', 'product', $product_id, [
        'name' => $name,
        'slug' => $slug,
        'images_count' => count($image_urls)
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Product created successfully',
        'data' => [
            'id' => (string)$product_id,
            'product_id' => $product_id,
            'product' => $createdProduct,
            'images' => $image_urls
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
