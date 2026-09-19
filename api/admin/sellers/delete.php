<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);

if (!$id) {
    json_response(['success' => false, 'message' => 'Seller ID is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM seller_profiles WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $seller = $stmt->fetch();
    if (!$seller) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Seller not found'], 404);
    }

    $stmt = $pdo->prepare("UPDATE seller_profiles SET status = 'Suspended', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    log_activity($admin_id, 'seller_deleted', 'seller', $id, ['company_name' => $seller['company_name']]);

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Seller deleted successfully']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
