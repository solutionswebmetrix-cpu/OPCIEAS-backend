<?php
require __DIR__ . '/../config/config.php';

echo '=== BASELINE DB COUNT SNAPSHOT (BEFORE MIGRATION) ===' . PHP_EOL . PHP_EOL;

$stmt = $pdo->query('SELECT COUNT(*) as total FROM categories');
$row = $stmt->fetch();
echo 'CATEGORIES TOTAL: ' . ($row['total'] ?? 0) . PHP_EOL;

$stmt = $pdo->query('SELECT id, slug, name, status, is_featured, sort_order, created_at FROM categories ORDER BY id ASC');
$cats = $stmt->fetchAll();
echo PHP_EOL . 'CATEGORIES LIST:' . PHP_EOL;
echo 'ID  | slug                       | name                        | status   | featured | created_at' . PHP_EOL;
echo str_repeat('-', 120) . PHP_EOL;
foreach ($cats as $c) {
    $dt = is_string($c['created_at']) ? substr($c['created_at'], 0, 10) : '';
    printf("%3s | %-26s | %-27s | %-8s | %-8s | %s\n",
        $c['id'], $c['slug'], substr($c['name'], 0, 27), $c['status'],
        ($c['is_featured'] ? 'YES' : 'no'), $dt);
}

echo PHP_EOL . 'PER-CATEGORY PRODUCT COUNTS (ALL statuses):' . PHP_EOL;
$stmt = $pdo->query('SELECT c.id, c.name, COUNT(p.id) as cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id, c.name ORDER BY c.id');
$perCatCounts = [];
foreach ($stmt->fetchAll() as $r) {
    $perCatCounts[$r['id']] = (int)$r['cnt'];
    printf("%3s. %-27s: %d products\n", $r['id'], substr($r['name'], 0, 27), $r['cnt']);
}

echo PHP_EOL . 'PER-CATEGORY PUBLISHED ONLY:' . PHP_EOL;
$stmt = $pdo->query("SELECT c.id, c.name, COUNT(p.id) as cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.status='Published' GROUP BY c.id, c.name ORDER BY c.id");
foreach ($stmt->fetchAll() as $r) {
    printf("%3s. %-27s: %d Published\n", $r['id'], substr($r['name'], 0, 27), $r['cnt']);
}

echo PHP_EOL . 'PRODUCTS BY STATUS:' . PHP_EOL;
$stmt = $pdo->query('SELECT status, COUNT(*) as cnt FROM products GROUP BY status ORDER BY cnt DESC');
$rows = $stmt->fetchAll();
$totalProducts = 0;
foreach ($rows as $r) {
    printf("  %-12s: %d\n", $r['status'], $r['cnt']);
    $totalProducts += (int)$r['cnt'];
}
echo '  TOTAL ALL STATUS: ' . $totalProducts . PHP_EOL;

$stmt = $pdo->query('SELECT COUNT(*) as orphan FROM products p WHERE NOT EXISTS (SELECT 1 FROM categories c WHERE c.id = p.category_id)');
$r = $stmt->fetch();
echo PHP_EOL . 'ORPHAN PRODUCTS (invalid category_id): ' . ($r['orphan'] ?? 0) . PHP_EOL;

echo PHP_EOL . 'IMAGES:' . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(*) as total FROM product_images');
$r = $stmt->fetch();
echo '  PRODUCT_IMAGES ROWS: ' . ($r['total'] ?? 0) . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(DISTINCT product_id) as with_images FROM product_images');
$r = $stmt->fetch();
echo '  PRODUCTS WITH >= 1 GALLERY IMAGE: ' . ($r['with_images'] ?? 0) . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(*) as cnt FROM product_images WHERE is_primary = 1');
$r = $stmt->fetch();
echo '  PRODUCTS WITH PRIMARY (via is_primary=1): ' . ($r['cnt'] ?? 0) . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(*) as cnt FROM product_specs');
$r = $stmt->fetch();
echo PHP_EOL . 'PRODUCT_SPEC ROWS (key/value): ' . ($r['cnt'] ?? 0) . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(DISTINCT product_id) as cnt FROM product_specs');
$r = $stmt->fetch();
echo 'PRODUCTS WITH >= 1 SPEC: ' . ($r['cnt'] ?? 0) . PHP_EOL;

echo PHP_EOL . 'Approval / moderation flags:' . PHP_EOL;
$stmt = $pdo->query('SELECT is_approved, COUNT(*) as cnt FROM products GROUP BY is_approved');
foreach ($stmt->fetchAll() as $r) {
    echo "  is_approved=" . ($r['is_approved'] ?? 'NULL') . ": " . $r['cnt'] . PHP_EOL;
}
echo PHP_EOL . 'Done.' . PHP_EOL;
