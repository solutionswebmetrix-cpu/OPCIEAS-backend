<?php
require_once __DIR__ . '/../../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
$status = $input['status'] ?? null;

if (!$id) {
    json_response(['success' => false, 'message' => 'Requirement ID is required'], 400);
}

$validStatuses = ['Pending', 'Approved', 'Rejected', 'Deleted', 'Fake', 'Closed'];
if (!in_array($status, $validStatuses)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT pr.*, bp.id AS buyer_profile_id, u.email AS buyer_email, u.name AS buyer_name, bp.status AS buyer_status
        FROM purchase_requirements pr
        LEFT JOIN buyer_profiles bp ON pr.buyer_id = bp.id
        LEFT JOIN users u ON bp.user_id = u.id
        WHERE pr.id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $req = $stmt->fetch();
    if (!$req) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Requirement not found'], 404);
    }

    $oldStatus = $req['status'];

    $stmt = $pdo->prepare("UPDATE purchase_requirements SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($status === 'Fake' && $req['buyer_profile_id']) {
        log_activity($admin_id, 'buyer_status_changed_fake', 'buyer', $req['buyer_profile_id'], [
            'old_status' => $req['buyer_status'],
            'new_status' => 'Suspended',
            'reason' => 'Fake requirement #' . $id
        ]);
    }

    if (in_array($status, ['Approved', 'Rejected']) && !empty($req['buyer_email'])) {
        send_requirement_status_email($req['buyer_email'], $req['buyer_name'] ?: 'Buyer', $status, $id);
    }

    log_activity($admin_id, 'requirement_status_changed', 'purchase_requirement', $id, [
        'old_status' => $oldStatus,
        'new_status' => $status,
        'title' => $req['title']
    ]);

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Requirement status updated']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
