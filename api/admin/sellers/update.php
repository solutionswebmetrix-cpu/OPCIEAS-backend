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
    json_response(['success' => false, 'message' => 'Seller ID is required'], 400);
}

$validStatuses = ['Pending', 'Approved', 'Rejected', 'Suspended', 'Deleted'];
if ($status && !in_array($status, $validStatuses, true)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
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

    $oldStatus = $seller['status'];
    $newStatus = $status ?: $oldStatus;

    $tableStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'supplier_applications'");
    $tableStmt->execute();
    if ((int)$tableStmt->fetchColumn() > 0) {
        $stmt = $pdo->prepare("SELECT id FROM supplier_applications WHERE user_id = ? LIMIT 1");
        $stmt->execute([$seller['user_id']]);
        $application = $stmt->fetch();
        if ($application) {
            $appStmt = $pdo->prepare("UPDATE supplier_applications SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $appStmt->execute([$newStatus, $input['verification_remarks'] ?? null, $admin_id, $application['id']]);
        }
    }

    $fields = [];
    $params = [];

    $allowedFields = [
        'status', 'company_name', 'business_type', 'phone', 'website', 'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode', 'gst_number', 'pan_number', 'registration_number', 'description', 'verification_status', 'verification_remarks'
    ];
    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $input)) {
            $fields[] = "$f = ?";
            $params[] = $input[$f];
        }
    }

    if ($status) {
        $fields[] = 'status = ?';
        $params[] = $newStatus;
    }

    if ($fields) {
        $sql = "UPDATE seller_profiles SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    $details = [];
    if ($status && $oldStatus !== $newStatus) {
        $details['old_status'] = $oldStatus;
        $details['new_status'] = $newStatus;
        log_activity($admin_id, 'seller_status_changed', 'seller', $id, $details);
    }

    log_activity($admin_id, 'seller_updated', 'seller', $id, $input);

    $stmt = $pdo->prepare("SELECT sp.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone FROM seller_profiles sp LEFT JOIN users u ON sp.user_id = u.id WHERE sp.id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Seller updated successfully', 'data' => $updated]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
