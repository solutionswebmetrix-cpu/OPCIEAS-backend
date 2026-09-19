<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$slug = trim((string)($_GET['slug'] ?? ''));
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($slug) && !$id) {
    json_response(['success' => false, 'message' => 'Product slug or id is required'], 400);
}

try {
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.description as category_description
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE " . ($id ? 'p.id = ?' : 'p.slug = ? AND p.status = "Published"') . "
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($id ? [$id] : [$slug]);
    $product = $stmt->fetch();

    if (!$product) {
        json_response(['success' => false, 'message' => 'Product not found'], 404);
    }

    $gStmt = $pdo->prepare("SELECT id, COALESCE(NULLIF(image_url, ''), image_path) AS image_url, image_path, alt_text, sort_order, is_primary FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $gStmt->execute([$product['id']]);
    $images = $gStmt->fetchAll();
    $product['images'] = $images;
    $product['gallery'] = array_map(function ($image) {
        return $image['image_url'] ?? $image['image_path'] ?? null;
    }, $images);
    $product['image'] = $product['image'] ?? ($product['gallery'][0] ?? null);

    $specs = [];
    $sStmt = $pdo->prepare("SELECT spec_key, spec_value FROM product_specs WHERE product_id = ? ORDER BY id ASC");
    $sStmt->execute([$product['id']]);
    foreach ($sStmt->fetchAll() as $s) {
        $specs[$s['spec_key']] = $s['spec_value'];
    }
    $product['specs'] = $specs;

    if (!empty($product['specifications'])) {
        $decoded = json_decode($product['specifications'], true);
        if (is_array($decoded)) {
            $product['specs'] = array_merge($decoded, $product['specs']);
        }
    }
    if (!empty($product['dimensions'])) {
        $decoded = json_decode($product['dimensions'], true);
        if (is_array($decoded)) {
            $product['specs']['Dimensions'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }
    }
    if (!empty($product['material'])) {
        $product['specs']['Materials Used'] = $product['material'];
    }
    if (!empty($product['warranty_months'])) {
        $product['specs']['Warranty'] = $product['warranty_months'] . ' Months Warranty';
    }
    if (!empty($product['specs']['Export Available'])) {
        $product['export_available'] = strtolower((string)$product['specs']['Export Available']) === 'yes';
    }

    json_response([
        'success' => true,
        'data' => $product
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch product: ' . $e->getMessage()], 500);
}
