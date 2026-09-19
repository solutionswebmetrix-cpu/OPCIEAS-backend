<?php
require __DIR__ . '/../config/config.php';

echo '=== DESCRIBE categories:' . PHP_EOL;
$stmt = $pdo->query('DESCRIBE categories');
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    $f = isset($r['Field']) ? $r['Field'] : '';
    $t = isset($r['Type']) ? $r['Type'] : '';
    $n = isset($r['Null']) ? $r['Null'] : '';
    echo "  $f | $t | $n" . PHP_EOL;
}

echo PHP_EOL . '=== DESCRIBE products cols:' . PHP_EOL;
$stmt = $pdo->query('DESCRIBE products');
$rows = $stmt->fetchAll();
$i = 0;
foreach ($rows as $r) {
    $f = isset($r['Field']) ? $r['Field'] : '';
    $t = isset($r['Type']) ? $r['Type'] : '';
    echo "  $f | $t" . PHP_EOL;
    if (++$i > 30) break;
}
