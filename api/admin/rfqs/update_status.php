<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../helpers/upload.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = isset($input['id']) ? (int)$input['id'] : (isset($input['rfq_id']) ? (int)$input['rfq_id'] : 0);
$status = trim((string)($input['status'] ?? ''));
$notes = isset($input['notes']) ? trim((string)$input['notes']) : '';
$reason = isset($input['reason']) ? trim((string)$input['reason']) : (isset($input['rejection_reason']) ? trim((string)$input['rejection_reason']) : '');
$quoteNumber = isset($input['quote_number']) ? trim((string)$input['quote_number']) : '';
$price = isset($input['price']) ? (float)$input['price'] : null;
$gstAmount = isset($input['gst_amount']) ? (float)$input['gst_amount'] : 0.0;
$currency = isset($input['currency']) ? trim((string)$input['currency']) : 'INR';
$deliveryTime = isset($input['delivery_time']) ? trim((string)$input['delivery_time']) : '';
$paymentTerms = isset($input['payment_terms']) ? trim((string)$input['payment_terms']) : '';
$validity = isset($input['validity']) ? trim((string)$input['validity']) : '';
$remarks = isset($input['remarks']) ? trim((string)$input['remarks']) : '';
$attachmentPath = null;

if (!$id || $status === '') {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

$allowed = ['New', 'In Review', 'Quoted', 'Closed', 'Rejected'];
$normalized = in_array($status, $allowed, true) ? $status : null;
if ($normalized === null) {
    $map = [
        'new' => 'New',
        'in_review' => 'In Review',
        'reviewing' => 'In Review',
        'quoted' => 'Quoted',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
    ];
    $normalized = $map[strtolower($status)] ?? null;
}

if ($normalized === null) {
    json_response(['success' => false, 'message' => 'Unsupported status'], 400);
}

if ($normalized === 'Quoted' && ($quoteNumber === '' || $price === null || $price < 0)) {
    json_response(['success' => false, 'message' => 'Quote number and price are required'], 400);
}

if ($normalized === 'Rejected' && $reason === '') {
    json_response(['success' => false, 'message' => 'Rejection reason is required'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, status FROM rfqs WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $rfq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rfq) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'RFQ not found'], 404);
    }

    $fieldList = ['status = ?', 'updated_by = ?', 'updated_at = NOW()'];
    $values = [$normalized, $admin_id];

    if ($normalized === 'Closed') {
        $fieldList[] = 'closed_by = ?';
        $values[] = $admin_id;
        $fieldList[] = 'closed_at = NOW()';
    } else {
        $fieldList[] = 'closed_by = ?';
        $values[] = null;
        $fieldList[] = 'closed_at = NULL';
    }

    if ($notes !== '') {
        $fieldList[] = 'admin_notes = ?';
        $values[] = $notes;
    }
    if ($normalized === 'Rejected' && $reason !== '') {
        $fieldList[] = 'rejection_reason = ?';
        $values[] = $reason;
    } elseif ($normalized !== 'Rejected') {
        $fieldList[] = 'rejection_reason = ?';
        $values[] = null;
    }

    $values[] = $id;

    $stmt = $pdo->prepare('UPDATE rfqs SET ' . implode(', ', $fieldList) . ' WHERE id = ?');
    $stmt->execute($values);

    if ($normalized === 'Quoted') {
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['attachment'], 'rfq-quotes');
            if ($upload['success']) {
                $attachmentPath = $upload['path'];
            }
        }
        $stmt = $pdo->prepare('INSERT INTO rfq_quotes (rfq_id, quote_number, price, currency, gst_amount, delivery_time, payment_terms, validity, remarks, attachment_path, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$id, $quoteNumber, $price, $currency, $gstAmount, $deliveryTime, $paymentTerms, $validity, $remarks, $attachmentPath, $admin_id]);

        $stmt = $pdo->prepare('UPDATE rfqs SET total_quotes = total_quotes + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    $activityDetails = ['status' => $normalized];
    if ($normalized === 'Quoted') {
        $activityDetails['quote_number'] = $quoteNumber;
        $activityDetails['price'] = $price;
    }
    if ($normalized === 'Rejected' && $reason !== '') {
        $activityDetails['reason'] = $reason;
    }

    log_activity($admin_id, 'rfq_status_changed', 'rfq', $id, $activityDetails);
    create_notification(null, 'rfq', 'RFQ status updated', 'An RFQ status was changed in the admin panel.', $id, 'rfq', 'normal', $activityDetails);

    try {
        if ($normalized === 'Quoted' && !empty($quoteNumber)) {
            $emailBody = '<html><body><h2>Quotation prepared for RFQ #' . $id . '</h2><p>Quote Number: ' . e($quoteNumber) . '</p><p>Amount: ' . e($price) . ' ' . e($currency) . '</p><p>Remarks: ' . e($remarks) . '</p></body></html>';
            send_email(SITE_EMAIL, 'RFQ Quote Prepared #' . $id, $emailBody);
        }
    } catch (Throwable $e) {
        // Ignore SMTP errors and continue the workflow.
    }

    $pdo->commit();

    json_response(['success' => true, 'message' => 'RFQ status updated', 'data' => ['status' => $normalized, 'quote_number' => $quoteNumber, 'price' => $price]]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
