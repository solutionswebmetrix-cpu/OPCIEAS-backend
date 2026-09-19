<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("
        SELECT
            c.id,
            c.parent_id,
            c.name,
            c.slug,
            c.description,
            c.image,
            c.icon,
            c.sort_order,
            c.is_featured,
            CASE WHEN c.status = 'active' THEN 'Active' ELSE 'Draft' END AS status,
            c.meta_title,
            c.meta_description,
            c.created_at,
            c.updated_at,
            COALESCE(pc.cnt, 0) AS total_products
        FROM categories c
        LEFT JOIN (
            SELECT category_id, COUNT(*) AS cnt FROM products GROUP BY category_id
        ) pc ON pc.category_id = c.id
        ORDER BY c.sort_order ASC, c.name ASC
    ");
    $categories = $stmt->fetchAll();

    $totalCategories = count($categories);
    $activeCount = 0;
    $featuredCount = 0;
    $totalProducts = 0;
    foreach ($categories as $c) {
        if ($c['status'] === 'Active') $activeCount++;
        if (!empty($c['is_featured'])) $featuredCount++;
        $totalProducts += (int)($c['total_products'] ?? 0);
    }

    json_response([
        'success' => true,
        'summary' => [
            'total_categories' => $totalCategories,
            'total_active' => $activeCount,
            'total_featured' => $featuredCount,
            'total_products' => $totalProducts,
        ],
        'data' => $categories
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch categories: ' . $e->getMessage()], 500);
}
