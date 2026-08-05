<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_auth('buyer');

$input = get_input();

$required = ['product_name', 'quantity', 'unit', 'description', 'country', 'state', 'city', 'delivery_address', 'expected_delivery'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $f)) . ' is required'], 400);
    }
}

$buyerId = $_SESSION['user_id'];
$explanationNote = trim($input['explanation_note'] ?? '');

try {
    $stmt = $pdo->prepare("INSERT INTO purchase_requirements 
        (buyer_id, product_name, quantity, unit, description, country, state, city, delivery_address, expected_delivery, explanation_note, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->execute([
        $buyerId,
        trim($input['product_name']),
        trim($input['quantity']),
        trim($input['unit']),
        trim($input['description']),
        trim($input['country']),
        trim($input['state']),
        trim($input['city']),
        trim($input['delivery_address']),
        trim($input['expected_delivery']),
        $explanationNote
    ]);
    $id = $pdo->lastInsertId();

    json_response([
        'success' => true,
        'message' => 'Purchase requirement submitted successfully',
        'id' => $id
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to submit: ' . $e->getMessage()], 500);
}
