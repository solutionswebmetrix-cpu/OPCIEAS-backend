<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$seller_id = (int)($input['seller_id'] ?? 0);
$message = trim($input['message'] ?? $input['note'] ?? '');

if (!$seller_id) {
    json_response(['success' => false, 'message' => 'Seller ID is required'], 400);
}
if (!$message) {
    json_response(['success' => false, 'message' => 'Message is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT sp.*, u.name AS contact_name, u.email AS email FROM seller_profiles sp LEFT JOIN users u ON sp.user_id = u.id WHERE sp.id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch();
    if (!$seller) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Seller not found'], 404);
    }

    $sellerName = $seller['contact_name'] ?: $seller['company_name'] ?: 'Seller';
    $sent = send_need_details_email($seller['email'], $sellerName, $message);

    log_activity($admin_id, 'seller_details_email_sent', 'seller', $seller_id, [
        'to_email' => $seller['email'],
        'message' => $message,
        'sent' => $sent
    ]);

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Email sent successfully', 'data' => ['sent' => $sent]]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
