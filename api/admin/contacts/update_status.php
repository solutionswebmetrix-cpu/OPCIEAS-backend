<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$id = isset($input['id']) ? (int)$input['id'] : 0;
$status = trim((string)($input['status'] ?? ''));

if (!$id || $status === '') {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

$normalized = strtolower($status);
$allowed = [
    'read' => 'new',
    'resolved' => 'resolved',
    'assigned' => 'assigned',
    'replied' => 'resolved',
    'spam' => 'spam',
];

if (!isset($allowed[$normalized])) {
    json_response(['success' => false, 'message' => 'Unsupported status'], 400);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, status, assigned_to FROM contacts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $contact = $stmt->fetch();
    if (!$contact) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Contact not found'], 404);
    }

    $newStatus = $allowed[$normalized];
    $assignedTo = trim((string)($input['assigned_to'] ?? $contact['assigned_to'] ?? ''));
    $reply = trim((string)($input['reply'] ?? ''));

    $statusValue = $newStatus;
    if ($normalized === 'read') {
        $statusValue = 'new';
    } elseif ($normalized === 'resolved' || $normalized === 'replied') {
        $statusValue = 'resolved';
    } elseif ($normalized === 'spam') {
        $statusValue = 'spam';
    }

    $setParts = ['status = ?'];
    $params = [$statusValue];

    if ($assignedTo !== '') {
        $setParts[] = 'assigned_to = ?';
        $params[] = $assignedTo;
    }

    if ($reply !== '') {
        $setParts[] = 'reply = ?';
        $params[] = $reply;
    }

    $params[] = $id;
    $stmt = $pdo->prepare("UPDATE contacts SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = ?");
    $stmt->execute($params);

    $pdo->commit();

    log_activity($admin_id, 'contact_status_changed', 'contact', $id, ['status' => $newStatus, 'assigned_to' => $assignedTo, 'reply' => $reply]);
    create_notification(null, 'message', 'Contact updated', 'A contact submission was updated in the admin panel.', $id, 'contact', 'normal', ['status' => $newStatus]);

    json_response(['success' => true, 'message' => 'Contact status updated']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
