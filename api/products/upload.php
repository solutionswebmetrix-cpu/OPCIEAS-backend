<?php
require_once __DIR__ . '/../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require __DIR__ . '/../../api/admin/products/images.php';
