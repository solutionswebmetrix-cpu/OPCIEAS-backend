<?php
require_once __DIR__ . '/../../../config/config.php';
$admin_id = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$type = $_GET['type'] ?? 'supplier';
$status = $_GET['status'] ?? null;
$search = trim((string)($_GET['search'] ?? ''));

if ($type === 'supplier') {
    $query = "SELECT * FROM supplier_applications";
    $where = [];
    $params = [];
    if ($status) { $where[] = 'status = ?'; $params[] = $status; }
    if ($search !== '') { $where[] = '(full_name LIKE ? OR company_name LIKE ? OR email LIKE ? OR phone_number LIKE ?)'; $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; }
    if ($where) { $query .= ' WHERE ' . implode(' AND ', $where); }
    $query .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    json_response(['success' => true, 'data' => ['items' => $items]]);
}

$query = "SELECT * FROM buyer_applications";
$where = [];
$params = [];
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($search !== '') { $where[] = '(full_name LIKE ? OR company_name LIKE ? OR email LIKE ? OR phone_number LIKE ?)'; $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; }
if ($where) { $query .= ' WHERE ' . implode(' AND ', $where); }
$query .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
json_response(['success' => true, 'data' => ['items' => $items]]);
