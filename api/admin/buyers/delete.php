<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);

if (!$id) {
    json_response(['success' => false, 'message' => 'Buyer ID is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM buyer_profiles WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $buyer = $stmt->fetch();
    if (!$buyer) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Buyer not found'], 404);
    }

    $stmt = $pdo->prepare("UPDATE buyer_profiles SET status = 'Deleted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    log_activity($admin_id, 'buyer_deleted', 'buyer', $id, [
        'company_name' => $buyer['company_name']
    ]);

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Buyer deleted successfully']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
