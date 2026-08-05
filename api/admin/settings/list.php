<?php
require_once __DIR__ . '/../../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT `key`, `value`, type, description FROM settings ORDER BY `key` ASC");
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $r) {
        $val = $r['value'];
        if ($r['type'] === 'json') {
            $decoded = json_decode($val, true);
            $val = $decoded === null ? $val : $decoded;
        } elseif ($r['type'] === 'boolean') {
            $val = (bool)$val;
        } elseif ($r['type'] === 'integer' || $r['type'] === 'number') {
            $val = (strpos($val, '.') !== false) ? (float)$val : (int)$val;
        }
        $settings[$r['key']] = [
            'value' => $val,
            'type' => $r['type'],
            'description' => $r['description']
        ];
    }

    json_response(['success' => true, 'data' => $settings]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
