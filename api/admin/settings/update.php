<?php
require_once __DIR__ . '/../../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$settings = $input['settings'] ?? (isset($input['key']) ? [$input['key'] => $input['value'] ?? null] : null);

if (!$settings || !is_array($settings)) {
    json_response(['success' => false, 'message' => 'Provide settings object or key+value pair'], 400);
}

try {
    $pdo->beginTransaction();

    $updated = 0;
    $inserted = 0;

    foreach ($settings as $key => $value) {
        $key = trim((string)$key);
        if ($key === '') continue;

        $type = 'string';
        $raw = $value;
        if (is_array($value) || is_object($value)) {
            $raw = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $raw = $value ? '1' : '0';
            $type = 'boolean';
        } elseif (is_int($value) || is_float($value)) {
            $raw = (string)$value;
            $type = is_float($value) ? 'number' : 'integer';
        } else {
            $raw = (string)$value;
        }

        $stmt = $pdo->prepare("SELECT id FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET `value` = ?, type = ?, updated_at = NOW() WHERE `key` = ?");
            $stmt->execute([$raw, $type, $key]);
            $updated++;
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`, type, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$key, $raw, $type]);
            $inserted++;
        }
    }

    log_activity($admin_id, 'settings_updated', 'settings', null, [
        'settings_count' => count($settings),
        'updated' => $updated,
        'inserted' => $inserted,
        'keys' => array_keys($settings)
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Settings saved successfully',
        'data' => ['updated' => $updated, 'inserted' => $inserted]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
