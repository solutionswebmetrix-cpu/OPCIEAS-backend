<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthMiddleware
{
    public static function checkAuth()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if ($headers === false) {
            $headers = [];
        }
        $token = null;

        foreach ($headers as $hName => $hValue) {
            if (strtolower($hName) === 'authorization') {
                if (preg_match('/Bearer\s+(.*)$/i', $hValue, $matches)) {
                    $token = $matches[1];
                    break;
                }
            }
        }

        if ($token !== null) {
            $db = Database::getInstance();
            $stmt = $db->query('SELECT * FROM users WHERE remember_token = ? AND status IN (?,?)', [$token, STATUS_APPROVED, 'active']);
            $user = $stmt->fetch();
            if ($user) {
                return $user;
            }
            $stmt = $db->query('SELECT * FROM admin_users WHERE remember_token = ? AND status = ?', [$token, 'active']);
            $admin = $stmt->fetch();
            if ($admin) {
                $admin['role'] = $admin['role'];
                $admin['name'] = $admin['full_name'] ?? $admin['username'];
                return $admin;
            }
        }

        if (is_logged_in()) {
            $user = get_current_user();
            if ($user) {
                $statusOk = true;
                if (isset($user['status'])) {
                    $okStatuses = [STATUS_APPROVED, STATUS_PENDING, 'active', STATUS_SUSPENDED];
                    $statusOk = in_array($user['status'], $okStatuses, true);
                }
                if ($statusOk) {
                    return $user;
                }
            }
        }

        json_response(['success' => false, 'error' => 'Unauthorized. Please log in.'], 401);
    }

    public static function checkRole($roles)
    {
        $user = self::checkAuth();
        $rolesArray = is_array($roles) ? $roles : [$roles];

        $adminLevelRoles = ['admin', 'super_admin', 'manager', 'editor'];
        if (array_intersect($rolesArray, ['admin'])) {
            $rolesArray = array_unique(array_merge($rolesArray, $adminLevelRoles));
        }

        $userRole = $user['role'] ?? '';
        if (!in_array($userRole, $rolesArray, true)) {
            json_response(['success' => false, 'error' => 'Forbidden. Insufficient permissions.'], 403);
        }

        return $user;
    }
}

if (!function_exists('checkAuth')) {
function checkAuth()
{
    return AuthMiddleware::checkAuth();
}
}

if (!function_exists('checkRole')) {
function checkRole($roles)
{
    return AuthMiddleware::checkRole($roles);
}
}
