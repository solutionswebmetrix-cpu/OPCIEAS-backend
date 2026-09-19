<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);

if (!$id) {
    json_response(['success' => false, 'message' => 'Product ID is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Product not found'], 404);
    }

    $fields = [];
    $params = [];

    $allowed = [
        'name', 'slug', 'category_id', 'sku', 'short_description', 'description',
        'price', 'discount_price', 'stock_quantity', 'is_featured', 'status', 'seller_id',
        'features', 'specifications', 'dimensions', 'material', 'meta_title', 'meta_description',
        'color', 'warranty_months', 'min_order_quantity', 'max_order_quantity', 'unit'
    ];
    foreach ($allowed as $f) {
        if (!array_key_exists($f, $input)) continue;
        $val = $input[$f];
        if ($f === 'status') {
            $status = $val;
            if ($status === 'Draft') {
                $status = 'Hidden';
            } elseif ($status === 'Published') {
                $status = 'Published';
            } elseif ($status === 'Rejected') {
                $status = 'Rejected';
            } else {
                $status = 'Pending';
            }
            $val = $status;
        }
        if ($f === 'category_id' || $f === 'seller_id') {
            $val = (int)$val;
        }
        if ($f === 'price' || $f === 'discount_price') {
            $val = (float)$val;
        }
        if ($f === 'stock_quantity') {
            $val = (int)$val;
        }
        if ($f === 'is_featured') {
            $val = (int)$val;
        }
        if ($f === 'features') {
            $val = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : null;
        }
        if ($f === 'specifications' || $f === 'dimensions' || $f === 'variants' || $f === 'tags') {
            $val = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : ($val ? (string)$val : null);
        }
        if ($f === 'export_available') {
            $val = !empty($val) ? 1 : 0;
        }
        $fields[] = "$f = ?";
        $params[] = $val;
    }

    $details = [];
    if (isset($input['status']) && $input['status'] !== $product['status']) {
        $details['old_status'] = $product['status'];
        $details['new_status'] = $input['status'];
    }

    if ($fields) {
        $sql = "UPDATE products SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    log_activity($admin_id, 'product_updated', 'product', $id, $details ?: $input);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Product updated successfully', 'data' => $updated]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
