<?php
require __DIR__ . '/../config/config.php';

echo '=== DESCRIBE products (remaining cols after 30):' . PHP_EOL;
$stmt = $pdo->query('DESCRIBE products');
$rows = $stmt->fetchAll();
$i = 0;
foreach ($rows as $r) {
    $i++;
    if ($i <= 30) continue;
    $f = isset($r['Field']) ? $r['Field'] : '';
    $t = isset($r['Type']) ? $r['Type'] : '';
    echo "  $f | $t" . PHP_EOL;
}

echo PHP_EOL . '=== DESCRIBE product_images:' . PHP_EOL;
$stmt = $pdo->query('DESCRIBE product_images');
foreach ($stmt->fetchAll() as $r) {
    $f = isset($r['Field']) ? $r['Field'] : '';
    $t = isset($r['Type']) ? $r['Type'] : '';
    echo "  $f | $t" . PHP_EOL;
}

echo PHP_EOL . '=== DESCRIBE product_specs:' . PHP_EOL;
$stmt = $pdo->query('DESCRIBE product_specs');
foreach ($stmt->fetchAll() as $r) {
    $f = isset($r['Field']) ? $r['Field'] : '';
    $t = isset($r['Type']) ? $r['Type'] : '';
    echo "  $f | $t" . PHP_EOL;
}
