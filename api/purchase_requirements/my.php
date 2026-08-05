<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_auth('buyer');

$buyerId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, product_name, quantity, unit, description, country, state, city,
                           delivery_address, expected_delivery, explanation_note, status, created_at, updated_at
                           FROM purchase_requirements
                           WHERE buyer_id = ? AND status != 'Deleted'
                           ORDER BY created_at DESC");
    $stmt->execute([$buyerId]);
    $items = $stmt->fetchAll();

    json_response([
        'success' => true,
        'count' => count($items),
        'data' => $items
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to fetch: ' . $e->getMessage()], 500);
}
