<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$order_number = $input['order_number'] ?? ('ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)));
$buyer_id = !empty($input['buyer_id']) ? (int)$input['buyer_id'] : null;
$seller_id = !empty($input['seller_id']) ? (int)$input['seller_id'] : null;
$product_id = !empty($input['product_id']) ? (int)$input['product_id'] : null;
$quantity = !empty($input['quantity']) ? (int)$input['quantity'] : 1;
$unit_price = !empty($input['unit_price']) ? (float)$input['unit_price'] : 0;
$subtotal = $unit_price * $quantity;
$tax_amount = !empty($input['tax_amount']) ? (float)$input['tax_amount'] : 0;
$discount_amount = !empty($input['discount_amount']) ? (float)$input['discount_amount'] : 0;
$shipping_amount = !empty($input['shipping_amount']) ? (float)$input['shipping_amount'] : 0;
$handling_amount = !empty($input['handling_amount']) ? (float)$input['handling_amount'] : 0;
$total_amount = !empty($input['total_amount']) ? (float)$input['total_amount'] : (!empty($input['total_price']) ? (float)$input['total_price'] : ($subtotal + $tax_amount - $discount_amount + $shipping_amount + $handling_amount));
$status = $input['status'] ?? 'Pending';
$payment_status = $input['payment_status'] ?? 'pending';
$currency = $input['currency'] ?? 'INR';
$notes = $input['notes'] ?? null;

$validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'];
if (!in_array($status, $validStatuses)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

$validPaymentStatuses = ['Pending', 'Paid', 'Partially Paid', 'Failed', 'Refunded'];
if (!in_array($payment_status, $validPaymentStatuses)) {
    $payment_status = 'Pending';
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO orders (order_number, buyer_id, seller_id, product_id, quantity, unit_price, subtotal, tax_amount, discount_amount, shipping_amount, handling_amount, total_amount, currency, payment_status, status, buyer_notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$order_number, $buyer_id, $seller_id, $product_id, $quantity, $unit_price, $subtotal, $tax_amount, $discount_amount, $shipping_amount, $handling_amount, $total_amount, $currency, $payment_status, $status, $notes]);
    $order_id = (int)$pdo->lastInsertId();

    log_activity($admin_id, 'order_created', 'order', $order_id, [
        'order_number' => $order_number,
        'total_amount' => $total_amount,
        'status' => $status
    ]);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    $pdo->commit();

    json_response(['success' => true, 'message' => 'Order created successfully', 'data' => $order]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
