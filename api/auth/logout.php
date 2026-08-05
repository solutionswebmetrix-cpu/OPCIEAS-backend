<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

if (!empty($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/');
}
if (!empty($_COOKIE['user_remember'])) {
    setcookie('user_remember', '', time() - 3600, '/');
}

session_destroy();

json_response(['success' => true, 'message' => 'Logged out successfully']);
