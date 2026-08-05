<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function resolveUploadFile($pathOrUrl): ?string {
    if (!$pathOrUrl || !is_string($pathOrUrl)) return null;
    $candidates = [];
    if (str_starts_with($pathOrUrl, '/uploads/') || str_starts_with($pathOrUrl, 'uploads/')) {
        $rel = ltrim(preg_replace('#^/+uploads/#', '', $pathOrUrl), '/');
        $candidates[] = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    } elseif (str_starts_with($pathOrUrl, UPLOAD_URL)) {
        $rel = ltrim(substr($pathOrUrl, strlen(UPLOAD_URL)), '/');
        $candidates[] = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    }
    foreach ($candidates as $c) {
        if (file_exists($c) && is_file($c)) return $c;
    }
    return null;
}

$input = get_input();
$product_id = (int)($input['product_id'] ?? 0);
$action = $input['action'] ?? 'add';

if (!$product_id) {
    json_response(['success' => false, 'message' => 'Product ID is required'], 400);
}
if (!in_array($action, ['add', 'remove', 'replace_primary', 'set_primary'], true)) {
    json_response(['success' => false, 'message' => 'Action must be add, remove, replace_primary or set_primary'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Product not found'], 404);
    }

    $result_data = [];

    if ($action === 'add') {
        $added = [];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = upload_file($_FILES['image'], 'products');
            if (!$upload['success']) {
                $pdo->rollBack();
                json_response($upload, 400);
            }
            $stmt = $pdo->prepare("SELECT MAX(sort_order) AS max_sort FROM product_images WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $maxSort = (int)$stmt->fetchColumn() + 1;
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_url, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $upload['path'] ?? $upload['url'], $upload['url'] ?? '', $maxSort]);
            $added[] = ['id' => (int)$pdo->lastInsertId(), 'url' => $upload['url'], 'path' => $upload['path'] ?? $upload['url']];
            log_activity($admin_id, 'product_image_added', 'product', $product_id, ['url' => $upload['url']]);
        } elseif (isset($_FILES['image_uploads']) && is_array($_FILES['image_uploads']['tmp_name'])) {
            $count = count($_FILES['image_uploads']['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['image_uploads']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name' => $_FILES['image_uploads']['name'][$i],
                    'type' => $_FILES['image_uploads']['type'][$i],
                    'tmp_name' => $_FILES['image_uploads']['tmp_name'][$i],
                    'error' => $_FILES['image_uploads']['error'][$i],
                    'size' => $_FILES['image_uploads']['size'][$i],
                ];
                $upload = upload_file($file, 'products');
                if (!$upload['success']) continue;
                $stmt = $pdo->prepare("SELECT MAX(sort_order) AS max_sort FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $maxSort = (int)$stmt->fetchColumn() + 1;
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_url, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$product_id, $upload['path'] ?? $upload['url'], $upload['url'] ?? '', $maxSort]);
                $added[] = ['id' => (int)$pdo->lastInsertId(), 'url' => $upload['url'], 'path' => $upload['path'] ?? $upload['url']];
                log_activity($admin_id, 'product_image_added', 'product', $product_id, ['url' => $upload['url']]);
            }
        } elseif (!empty($input['image_url'])) {
            $url = trim($input['image_url']);
            $stmt = $pdo->prepare("SELECT MAX(sort_order) AS max_sort FROM product_images WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $maxSort = (int)$stmt->fetchColumn() + 1;
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_url, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $url, $url, $maxSort]);
            $added[] = ['id' => (int)$pdo->lastInsertId(), 'url' => $url];
            log_activity($admin_id, 'product_image_added', 'product', $product_id, ['url' => $url]);
        }
        $result_data['added'] = $added;
    } elseif ($action === 'set_primary') {
        $image_id = (int)($input['image_id'] ?? 0);
        if (!$image_id) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'image_id required for set_primary'], 400);
        }
        $pdo->prepare("UPDATE product_images SET is_primary = 0, updated_at = NOW() WHERE product_id = ?")->execute([$product_id]);
        $pdo->prepare("UPDATE product_images SET is_primary = 1, updated_at = NOW() WHERE id = ? AND product_id = ?")->execute([$image_id, $product_id]);
        $result_data['primary_image_id'] = $image_id;
        log_activity($admin_id, 'product_image_primary_set', 'product', $product_id, ['image_id' => $image_id]);
    } elseif ($action === 'remove' || $action === 'replace_primary') {
        $image_id = (int)($input['image_id'] ?? 0);
        $image_url = $input['image_url'] ?? null;
        $removed = 0;
        $removedFiles = 0;
        $targetRows = [];
        if ($image_id) {
            $s = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND id = ?");
            $s->execute([$product_id, $image_id]);
            $targetRows = $s->fetchAll();
        } elseif ($image_url) {
            $s = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND (image_url = ? OR image_path = ?)");
            $s->execute([$product_id, $image_url, $image_url]);
            $targetRows = $s->fetchAll();
        } elseif ($action === 'replace_primary') {
            $s = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY id ASC LIMIT 1");
            $s->execute([$product_id]);
            $targetRows = $s->fetchAll();
        }
        foreach ($targetRows as $r) {
            $filesToCheck = [resolveUploadFile($r['image_path'] ?? null), resolveUploadFile($r['image_url'] ?? null)];
            foreach (array_filter($filesToCheck) as $fp) {
                try { if (@unlink($fp)) $removedFiles++; } catch (Throwable $e) {}
            }
            $d = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
            $d->execute([(int)$r['id']]);
            $removed += $d->rowCount();
        }

        if ($action === 'replace_primary') {
            $newUploaded = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = upload_file($_FILES['image'], 'products');
                if ($upload['success']) $newUploaded = $upload;
            }
            if ($newUploaded) {
                $ins = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_url, sort_order, is_primary, created_at) VALUES (?, ?, ?, 0, 1, NOW())");
                $ins->execute([$product_id, $newUploaded['path'] ?? $newUploaded['url'], $newUploaded['url'] ?? '']);
                $newId = (int)$pdo->lastInsertId();
                $result_data['replaced_with'] = ['id' => $newId, 'url' => $newUploaded['url']];
                log_activity($admin_id, 'product_image_replaced', 'product', $product_id, [
                    'new_url' => $newUploaded['url'],
                    'old_records_removed' => $removed,
                    'old_files_removed' => $removedFiles,
                ]);
            }
        } else {
            log_activity($admin_id, 'product_image_removed', 'product', $product_id, [
                'image_id' => $image_id,
                'image_url' => $image_url,
                'removed' => $removed,
                'files_deleted' => $removedFiles,
            ]);
        }

        $result_data['removed'] = $removed;
        $result_data['files_deleted'] = $removedFiles;
    }

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Image action completed', 'data' => $result_data]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
