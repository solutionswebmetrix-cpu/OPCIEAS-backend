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
    json_response(['success' => false, 'message' => 'Order ID is required'], 400);
}

$validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'];
if (!in_array($status, $validStatuses)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Order not found'], 404);
    }

    $oldStatus = $order['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    $fields = [
        'order_number' => 'order_number',
        'quantity' => 'quantity',
        'unit_price' => 'unit_price',
        'subtotal' => 'subtotal',
        'tax_amount' => 'tax_amount',
        'discount_amount' => 'discount_amount',
        'shipping_amount' => 'shipping_amount',
        'handling_amount' => 'handling_amount',
        'total_amount' => 'total_amount',
        'currency' => 'currency',
        'payment_status' => 'payment_status',
        'notes' => 'buyer_notes'
    ];
    $updates = [];
    $params = [];
    foreach ($fields as $inputKey => $dbKey) {
        if (array_key_exists($inputKey, $input)) {
            $updates[] = "$dbKey = ?";
            $params[] = $input[$inputKey];
        }
    }
    if ($updates) {
        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?");
        $stmt->execute($params);
    }

    log_activity($admin_id, 'order_status_changed', 'order', $id, [
        'old_status' => $oldStatus,
        'new_status' => $status,
        'order_number' => $order['order_number']
    ]);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Order updated successfully', 'data' => $updated]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
