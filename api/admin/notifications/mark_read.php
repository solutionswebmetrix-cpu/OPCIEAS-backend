<?php
require_once __DIR__ . '/../../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$ids = $input['ids'] ?? null;
$id = isset($input['id']) ? (int)$input['id'] : 0;
$mark_all = isset($input['all']) && ($input['all'] === '1' || $input['all'] === true || $input['all'] === 'true');

try {
    $pdo->beginTransaction();
    $count = 0;

    if ($mark_all) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
        $stmt->execute();
        $count = $stmt->rowCount();
    } elseif ($ids && is_array($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id IN ($in)");
        $stmt->execute($ids);
        $count = $stmt->rowCount();
    } elseif ($id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $count = $stmt->rowCount();
    } else {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Provide id, ids array, or all=true'], 400);
    }

    $pdo->commit();

    json_response(['success' => true, 'message' => "Marked $count notification(s) as read", 'data' => ['marked_count' => $count]]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
