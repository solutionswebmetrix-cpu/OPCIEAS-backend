<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = rawurldecode($uri);

if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/index.php';
    return;
}

if (preg_match('#^/api/(sellers|buyers)/?$#', $uri, $m)) {
    require __DIR__ . '/api/' . $m[1] . '/index.php';
    return;
}

if (preg_match('#^/api/products/?$#', $uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require __DIR__ . '/api/products/create.php';
    } else {
        require __DIR__ . '/api/products/list.php';
    }
    return;
}

if (preg_match('#^/api/products/upload/?$#', $uri)) {
    require __DIR__ . '/api/products/upload.php';
    return;
}

if (preg_match('#^/api/([^/]+)/([^/]+)/([^/]+)\.php/?$#', $uri, $m)) {
    $resource = $m[1];
    $action = $m[2];
    $subaction = $m[3];
    $file = __DIR__ . '/api/' . $resource . '/' . $action . '/' . $subaction . '.php';
    if (is_file($file)) {
        require $file;
        return;
    }
}

if (preg_match('#^/api/([^/]+)/([^/]+)/?$#', $uri, $m)) {
    $resource = $m[1];
    $action = $m[2];
    $file = __DIR__ . '/api/' . $resource . '/' . $action . '.php';
    if (is_file($file)) {
        require $file;
        return;
    }
}

if (preg_match('#^/api/products/(list|get|create|update|delete|upload)\.php/?$#', $uri)) {
    $file = __DIR__ . '/api/products/' . preg_replace('#^/api/products/|\.php$#', '', $uri);
    if (is_file($file . '.php')) {
        require $file . '.php';
        return;
    }
}

if (preg_match('#^/api/products/([^/]+)/?$#', $uri, $m)) {
    $id = $m[1];
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $_POST['id'] = $id;
        require __DIR__ . '/api/products/delete.php';
    } else {
        $_GET['id'] = $id;
        require __DIR__ . '/api/products/get.php';
    }
    return;
}

if (preg_match('#^/api/([^/]+)/([^/]+)/?$#', $uri, $m)) {
    $resource = $m[1];
    $action = $m[2];
    $file = __DIR__ . '/api/' . $resource . '/' . $action . '.php';
    if (is_file($file)) {
        require $file;
        return;
    }
}

if (is_file(__DIR__ . $uri)) {
    return false;
}

if (is_dir(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
