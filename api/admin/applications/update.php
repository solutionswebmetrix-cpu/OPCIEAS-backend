<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$type = $input['type'] ?? 'supplier';
$id = (int)($input['id'] ?? 0);
$status = $input['status'] ?? null;
$reviewNotes = $input['review_notes'] ?? null;

if (!$id) {
    json_response(['success' => false, 'message' => 'Application ID is required'], 400);
}

$allowedStatuses = ['Pending','Approved','Rejected','Under Review'];
if ($status && !in_array($status, $allowedStatuses, true)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

$table = $type === 'buyer' ? 'buyer_applications' : 'supplier_applications';
try {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $application = $stmt->fetch();
    if (!$application) {
        json_response(['success' => false, 'message' => 'Application not found'], 404);
    }

    $stmt = $pdo->prepare("UPDATE $table SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status ?: ($application['status'] ?? 'Pending'), $reviewNotes, $admin_id, $id]);

    log_activity($admin_id, 'application_status_changed', $type === 'buyer' ? 'buyer' : 'seller', $id, ['status' => $status ?: $application['status'], 'review_notes' => $reviewNotes]);

    json_response(['success' => true, 'message' => 'Application updated successfully']);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
