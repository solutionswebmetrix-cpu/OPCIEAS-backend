<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
$status = $input['status'] ?? null;

if (!$id) {
    json_response(['success' => false, 'message' => 'Buyer ID is required'], 400);
}

$validStatuses = ['Approved', 'Rejected', 'Suspended', 'Deleted'];
if (!in_array($status, $validStatuses, true)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
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

    $oldStatus = $buyer['status'];

    $stmt = $pdo->prepare("UPDATE buyer_profiles SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    $stmt = $pdo->prepare("SELECT id FROM buyer_applications WHERE user_id = ? LIMIT 1");
    $stmt->execute([$buyer['user_id']]);
    $application = $stmt->fetch();
    if ($application) {
        $appStmt = $pdo->prepare("UPDATE buyer_applications SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?");
        $appStmt->execute([$status, $input['review_notes'] ?? null, $admin_id, $application['id']]);
    }

    log_activity($admin_id, 'buyer_status_changed', 'buyer', $id, [
        'old_status' => $oldStatus,
        'new_status' => $status,
        'company_name' => $buyer['company_name']
    ]);

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Buyer status updated']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
