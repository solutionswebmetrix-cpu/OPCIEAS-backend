<?php
$COOKIE = __DIR__ . '/_test_cookies.txt';
@unlink($COOKIE);
$BASE = 'http://127.0.0.1:8000/api';

function req($method, $path, $payload = null, $isForm = false, $attach = []) {
    global $COOKIE, $BASE;
    $url = $BASE . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $headers = ['Accept: application/json'];
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isForm) {
            $fields = $payload ?? [];
            foreach ($attach as $k => $file) {
                $fields[$k] = new CURLFile($file);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : '';
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if ($resp === false) {
        echo "CURL FAIL for $method $path : err=$err" . PHP_EOL;
    }
    curl_close($ch);
    $decoded = json_decode($resp, true);
    return ['code' => $code, 'raw' => $resp, 'json' => $decoded, 'err' => $err];
}

function echoln($s) { echo $s . PHP_EOL; }
function section($title) { echoln(PHP_EOL . '============================================================'); echoln(' ' . $title); echoln('============================================================'); }

/* ============================================================
   1. ADMIN LOGIN
   ============================================================ */
section('1. ADMIN LOGIN');
$r = req('POST', '/auth/login.php', ['email' => 'admin@opcieas.com', 'password' => 'password']);
echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' role=' . ($r['json']['data']['role'] ?? '?') . ' user=' . ($r['json']['data']['user']['name'] ?? '?'));
if (empty($r['json']['success'])) { echoln('FAIL DETAIL: ' . $r['raw']); exit(1); }

/* ============================================================
   2. LIST ADMIN PRODUCTS
   ============================================================ */
section('2. ADMIN PRODUCT LIST (status=any limit=5)');
$r = req('GET', '/products/list.php?limit=5');
$total = $r['json']['pagination']['total'] ?? '?';
$count = isset($r['json']['data']) ? count($r['json']['data']) : 0;
echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' TOTAL=' . $total . ' batch=' . $count);
$first = $r['json']['data'][0] ?? null;
if ($first) echoln('sample: id=' . $first['id'] . ' name=' . $first['name'] . ' cat=' . ($first['category_id'] ?? '?') . ' price=' . ($first['price'] ?? '?') . ' img_gallery=' . count($first['gallery'] ?? []));

/* ============================================================
   3. CREATE TEST PRODUCT
   ============================================================ */
section('3. CREATE TEST PRODUCT (CRUD: CREATE)');
$sample = [
    'name' => '[TEST] Premium Ergonomic Test Chair ' . date('His'),
    'category_id' => 1,
    'sku' => 'TEST-CHAIR-' . time(),
    'price' => '18500.00',
    'discount_price' => '15999.00',
    'stock_quantity' => 42,
    'status' => 'Published',
    'is_featured' => 1,
    'short_description' => 'Short test description for ergonomic premium office chair suitable for corporate workspaces with lumbar support.',
    'description' => 'Comprehensive test long description: This premium ergonomic office chair features adjustable lumbar support, breathable mesh back, 360-degree swivel, Class-4 gas lift, premium padded armrests, and a durable chrome-plated metal base. Tested for 150kg capacity and 100,000 cycles.',
    'features' => ['Adjustable lumbar support', 'Breathable mesh back', '360-degree swivel base', 'Class-4 gas lift', 'Premium padded armrests'],
    'specifications' => ['Material' => 'Mesh + Chrome Steel', 'Color' => 'Black', 'Warranty' => '36 Months', 'Max Load' => '150 kg'],
    'meta_title' => 'Test Premium Ergonomic Office Chair | OPCIEAS',
    'meta_description' => 'SEO meta: Buy premium ergonomic test office chair online with 3-year warranty, free installation across India.',
];
$r = req('POST', '/admin/products/create.php', $sample);
$created_id = $r['json']['data']['id'] ?? null;
echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?') . ' NEW_ID=' . $created_id);
if (empty($r['json']['success'])) { echoln('FAIL DETAIL: ' . $r['raw']); }

/* ============================================================
   4. UPLOAD + ATTACH IMAGE
   ============================================================ */
section('4. UPLOAD IMAGE (UPLOAD + ADD TO PRODUCT)');
$testImg = __DIR__ . '/_test_img.png';
file_put_contents($testImg, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
if ($created_id) {
    $r = req('POST', '/admin/products/images.php',
        ['action' => 'add', 'product_id' => $created_id, 'alt_text' => 'test chair image'],
        true,
        ['image' => $testImg]
    );
    echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?') . ' new_url=' . ($r['json']['data']['image_url'] ?? '?'));
    $img_url_1 = $r['json']['data']['image_url'] ?? null;
    $img_id_1  = $r['json']['data']['image_id'] ?? null;
    if (empty($r['json']['success'])) echoln('FAIL DETAIL: ' . $r['raw']);
}

/* ============================================================
   5. SET PRIMARY IMAGE
   ============================================================ */
section('5. SET PRIMARY IMAGE');
if (!empty($created_id) && !empty($img_id_1)) {
    $r = req('POST', '/admin/products/images.php', ['action' => 'set_primary', 'product_id' => $created_id, 'image_id' => (int)$img_id_1]);
    echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?'));
}

/* ============================================================
   6. UPDATE (EDIT) PRODUCT
   ============================================================ */
section('6. UPDATE/EDIT PRODUCT (CRUD: UPDATE)');
if ($created_id) {
    $upd = ['id' => $created_id, 'name' => '[TEST-UPDATED] Test Chair v2', 'price' => '19999.00', 'stock_quantity' => 88, 'status' => 'Published'];
    $r = req('POST', '/admin/products/update.php', $upd);
    echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?'));
    if ($r['json']['success'] ?? false) {
        $d = $r['json']['data'];
        echoln('  NEW name=' . ($d['name'] ?? '?') . ' price=' . ($d['price'] ?? '?') . ' stock=' . ($d['stock_quantity'] ?? '?') . ' status=' . ($d['status'] ?? '?'));
    } else {
        echoln('FAIL DETAIL: ' . $r['raw']);
    }
}

/* ============================================================
   7. REPLACE PRIMARY IMAGE (old file must be deleted)
   ============================================================ */
section('7. REPLACE PRIMARY IMAGE (upload new -> replace -> DELETE OLD FILE)');
if (!empty($created_id)) {
    $testImg2 = __DIR__ . '/_test_img2.png';
    file_put_contents($testImg2, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    $r = req('POST', '/admin/products/images.php',
        ['action' => 'replace_primary', 'product_id' => $created_id, 'alt_text' => 'replaced primary'],
        true,
        ['image' => $testImg2]
    );
    echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?'));
    echoln('  new_primary_url=' . ($r['json']['data']['image_url'] ?? '?') . ' files_deleted=' . ($r['json']['data']['files_deleted'] ?? '?'));
    @unlink($testImg2);
}

/* ============================================================
   8. VERIFY: FETCH PUBLIC LIST COUNT (before delete)
   ============================================================ */
section('8. VERIFY PUBLIC API COUNT');
$r = req('GET', '/products/list.php?status=Published&limit=1');
$totalAfterCreate = $r['json']['pagination']['total'] ?? '?';
echoln('PUBLISHED PRODUCTS AFTER CREATE: ' . $totalAfterCreate . ' (expected 113 + 1 = 114 if test was published)');

/* ============================================================
   9. DELETE PRODUCT (hard)
   ============================================================ */
section('9. DELETE PRODUCT (CRUD: DELETE)');
if ($created_id) {
    $r = req('POST', '/admin/products/delete.php', ['id' => $created_id, 'hard' => 1]);
    echoln('HTTP ' . $r['code'] . ' success=' . ($r['json']['success'] ?? '?') . ' msg=' . ($r['json']['message'] ?? '?'));
    echoln('  deleted_count=' . ($r['json']['data']['deleted_count'] ?? '?') . ' files_deleted=' . ($r['json']['data']['files_deleted'] ?? '?'));
}

/* ============================================================
   10. VERIFY PUBLIC COUNT AFTER DELETE
   ============================================================ */
section('10. VERIFY COUNT AFTER DELETE');
$r = req('GET', '/products/list.php?status=Published&limit=1');
$totalAfterDel = $r['json']['pagination']['total'] ?? '?';
echoln('PUBLISHED PRODUCTS AFTER DELETE: ' . $totalAfterDel);

/* ============================================================
   11. DB VIA SQL: verify no dangling rows
   ============================================================ */
section('11. FINAL DB COUNT VIA MYSQL');
@unlink($testImg);
$pdo = new PDO('mysql:host=localhost;dbname=opcieas;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$res = $pdo->query("SELECT 'products' t, COUNT(*) c FROM products UNION ALL SELECT 'product_images', COUNT(*) FROM product_images UNION ALL SELECT 'product_specs', COUNT(*) FROM product_specs UNION ALL SELECT 'published', COUNT(*) FROM products WHERE status='Published' UNION ALL SELECT 'categories', COUNT(*) FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($res as $k => $v) echoln('  DB ' . $k . ' = ' . $v);

echoln('');
echoln('========== ALL TESTS COMPLETE ==========');
