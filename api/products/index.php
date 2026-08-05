<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$path = trim((string)($_GET['path'] ?? ''), '/');
if ($path === '') {
    require __DIR__ . '/list.php';
    exit;
}

$segments = explode('/', $path);
if (count($segments) === 1) {
    $_GET['id'] = $segments[0];
    require __DIR__ . '/get.php';
    exit;
}

json_response(['success' => false, 'message' => 'Not found'], 404);
