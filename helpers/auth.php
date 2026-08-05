<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!defined('ROLE_ADMIN'))  define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_SELLER')) define('ROLE_SELLER', 'seller');
if (!defined('ROLE_BUYER'))  define('ROLE_BUYER', 'buyer');

if (!defined('STATUS_PENDING'))   define('STATUS_PENDING', 'Pending');
if (!defined('STATUS_APPROVED'))  define('STATUS_APPROVED', 'Approved');
if (!defined('STATUS_REJECTED'))  define('STATUS_REJECTED', 'Rejected');
if (!defined('STATUS_SUSPENDED')) define('STATUS_SUSPENDED', 'Suspended');
if (!defined('STATUS_DELETED'))   define('STATUS_DELETED', 'Deleted');

if (!function_exists('hash_password')) {
    function hash_password($password)
    {
        return password_hash((string)$password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

if (!function_exists('verify_password')) {
    function verify_password($password, $hash)
    {
        return password_verify((string)$password, (string)$hash);
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token()
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}

if (!function_exists('generate_csrf')) {
    function generate_csrf()
    {
        return generate_csrf_token();
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token)
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
            return false;
        }
        $issued = $_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0;
        if ($issued && (time() - $issued) > CSRF_EXPIRE_SECONDS) {
            unset($_SESSION[CSRF_TOKEN_NAME], $_SESSION[CSRF_TOKEN_NAME . '_time']);
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], (string)$token);
    }
}

if (!function_exists('generate_remember_token')) {
    function generate_remember_token()
    {
        return bin2hex(random_bytes(40));
    }
}

if (!function_exists('e')) {
    function e($value, $flags = ENT_QUOTES)
    {
        if ($value === null) return '';
        return htmlspecialchars((string)$value, $flags, 'UTF-8');
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return $_SESSION['user_id'] ?? ($_GET['_user_id'] ?? ($_POST['_user_id'] ?? null));
    }
}

if (!function_exists('get_current_user_role')) {
    function get_current_user_role()
    {
        return $_SESSION['role'] ?? null;
    }
}

if (!function_exists('get_current_user')) {
    function get_current_user()
    {
        if (!is_logged_in()) return null;
        $db   = Database::getInstance();
        $id   = get_current_user_id();
        $role = get_current_user_role();
        if (in_array($role, ['admin', 'super_admin', 'manager', 'editor'], true)) {
            $stmt = $db->query('SELECT * FROM admin_users WHERE id = ? LIMIT 1', [$id]);
            $u = $stmt->fetch();
            if ($u) $u['role'] = $u['role'] ?? 'admin';
            return $u;
        }
        $stmt = $db->query('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!is_logged_in()) {
            json_response(['success' => false, 'error' => 'Authentication required'], 401);
        }
    }
}

if (!function_exists('require_auth')) {
    function require_auth($role = null)
    {
        require_login();
        if ($role !== null) {
            return require_role($role);
        }
        return get_current_user_id();
    }
}

if (!function_exists('require_role')) {
    function require_role($role)
    {
        require_login();
        $userRole   = get_current_user_role();
        $rolesArray = is_array($role) ? $role : [$role];
        $adminRoles = ['admin', 'super_admin', 'manager', 'editor'];
        if (array_intersect($rolesArray, ['admin'])) {
            $rolesArray = array_values(array_unique(array_merge($rolesArray, $adminRoles)));
        }
        if (!in_array($userRole, $rolesArray, true)) {
            json_response(['success' => false, 'error' => 'Forbidden: Insufficient permissions'], 403);
        }
        return get_current_user_id();
    }
}
