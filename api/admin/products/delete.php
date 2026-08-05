<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
$hard = !isset($input['hard']) || (int)$input['hard'] !== 0;

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

    $imagePaths = [];
    $imgStmt = $pdo->prepare("SELECT id, image_path, image_url FROM product_images WHERE product_id = ?");
    $imgStmt->execute([$id]);
    foreach ($imgStmt->fetchAll() as $img) {
        foreach (['image_path', 'image_url'] as $col) {
            if (!empty($img[$col]) && is_string($img[$col])) {
                $candidate = null;
                if (str_starts_with($img[$col], '/uploads/') || str_starts_with($img[$col], 'uploads/')) {
                    $candidate = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, preg_replace('#^/+uploads/#', '', $img[$col])), '/\\');
                } elseif (str_starts_with($img[$col], UPLOAD_URL)) {
                    $candidate = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, substr($img[$col], strlen(UPLOAD_URL))), '/\\');
                }
                if ($candidate && file_exists($candidate) && is_file($candidate)) {
                    $imagePaths[] = $candidate;
                }
            }
        }
    }
    $imagePaths = array_values(array_unique($imagePaths));

    $productForLog = [
        'id' => $id,
        'name' => $product['name'],
        'slug' => $product['slug'],
        'image_count' => count($imagePaths),
    ];

    if ($hard) {
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_specs WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM orders WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("UPDATE products SET status = 'Hidden', updated_at = NOW() WHERE id = ?")->execute([$id]);
    }

    if ($hard && $imagePaths) {
        $deletedFiles = 0;
        foreach ($imagePaths as $fp) {
            try {
                if (@unlink($fp)) $deletedFiles++;
            } catch (Throwable $e) {
            }
        }
        $productForLog['files_deleted'] = $deletedFiles;
    }

    log_activity($admin_id, $hard ? 'product_hard_deleted' : 'product_deleted', 'product', $id, $productForLog);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => $hard ? 'Product permanently deleted' : 'Product deleted successfully',
        'data' => $productForLog,
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
