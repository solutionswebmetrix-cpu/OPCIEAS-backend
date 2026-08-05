<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_auth('buyer');

$input = get_input();
$id = intval($input['id'] ?? 0);

if (!$id) {
    json_response(['success' => false, 'message' => 'ID is required'], 400);
}

$buyerId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, buyer_id FROM purchase_requirements WHERE id = ? AND status != 'Deleted' LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        json_response(['success' => false, 'message' => 'Record not found'], 404);
    }
    if ($row['buyer_id'] != $buyerId) {
        json_response(['success' => false, 'message' => 'Forbidden'], 403);
    }

    $stmt = $pdo->prepare("UPDATE purchase_requirements SET status = 'Deleted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    json_response([
        'success' => true,
        'message' => 'Purchase requirement deleted successfully'
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()], 500);
}
