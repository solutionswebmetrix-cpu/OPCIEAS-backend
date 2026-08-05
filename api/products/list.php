<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$categoryId = $_GET['category_id'] ?? null;
$categorySlug = isset($_GET['categorySlug']) ? trim((string)$_GET['categorySlug']) : (isset($_GET['category_slug']) ? trim((string)$_GET['category_slug']) : '');
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(500, intval($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($status !== null && $status !== '' && strtolower($status) !== 'all') {
    $where[] = "p.status = ?";
    $params[] = $status;
}
if ($categoryId) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
} elseif ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON c.id = p.category_id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT p.id, p.name, p.slug, p.sku, p.category_id, p.price, p.sale_price, p.discount_price, p.short_description, p.description,
                   p.features, p.specifications, p.stock_quantity, p.status, p.is_featured as featured, p.meta_title, p.meta_description, p.created_at, p.updated_at,
                   c.name as category_name, c.slug as category_slug,
                   COALESCE(
                       (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1),
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1),
                       ''
                   ) as image,
                   COALESCE(
                       (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1),
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1),
                       ''
                   ) as thumbnail
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            $whereSql
            ORDER BY p.is_featured DESC, p.id DESC
            LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $productIds = array_column($products, 'id');
    $gallery = [];
    if ($productIds) {
        $ids = implode(',', array_fill(0, count($productIds), '?'));
        $gStmt = $pdo->prepare("SELECT product_id, image_url, image_path, sort_order, is_primary FROM product_images WHERE product_id IN ($ids) ORDER BY product_id, is_primary DESC, sort_order ASC, id ASC");
        $gStmt->execute($productIds);
        foreach ($gStmt->fetchAll() as $row) {
            $imgUrl = !empty($row['image_url']) ? $row['image_url'] : $row['image_path'];
            $gallery[$row['product_id']][] = ['image_url' => $imgUrl, 'image_path' => $row['image_path'], 'sort_order' => $row['sort_order'], 'is_primary' => $row['is_primary']];
        }
    }

    foreach ($products as &$p) {
        $p['gallery'] = array_map(function ($item) {
            return $item['image_url'] ?? $item['image_path'] ?? null;
        }, $gallery[$p['id']] ?? []);
        $p['images'] = $gallery[$p['id']] ?? [];
        if (empty($p['image']) && !empty($p['gallery'])) {
            $p['image'] = $p['gallery'][0];
        }
    }
    unset($p);

    json_response([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ]
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch products: ' . $e->getMessage()], 500);
}
