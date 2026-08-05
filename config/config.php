<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    $sessDir = __DIR__ . '/../sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0755, true);
    }
    if (is_dir($sessDir)) {
        ini_set('session.save_path', realpath($sessDir) ?: $sessDir);
    }
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if ($requestMethod !== 'CLI') {
        @session_start();
    }
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'opcieas');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', getenv('SITE_NAME') ?: 'OPCIEAS');
define('SITE_EMAIL', getenv('SITE_EMAIL') ?: 'admin@opcieas.com');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost:8000');
define('ADMIN_URL', SITE_URL . '/admin');

define('UPLOAD_DIR', realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads/'));
define('UPLOAD_URL', '/uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_DOC_EXT', ['pdf']);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_MIME', ['application/pdf']);

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: ((SMTP_PORT === 465) ? 'ssl' : 'tls'));
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@opcieas.com');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_FROM);
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: SITE_NAME);

define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_EXPIRE_SECONDS', 7200);
define('SESSION_TIMEOUT_SECONDS', 8 * 60 * 60);

define('STATUS_PENDING', 'Pending');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');
define('STATUS_SUSPENDED', 'Suspended');
define('STATUS_DELETED', 'Deleted');
define('STATUS_CLOSED', 'Closed');
define('STATUS_FAKE', 'Fake');
define('STATUS_PUBLISHED', 'Published');
define('STATUS_HIDDEN', 'Hidden');
define('STATUS_DRAFT', 'Draft');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed. Please check DB configuration.',
        'debug'   => (getenv('APP_ENV') === 'development') ? $e->getMessage() : null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_SESSION['user_id']) && isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!function_exists('json_response')) {
    function json_response($data, $code = 200)
    {
        http_response_code($code);
        if (!is_array($data) && !is_object($data)) {
            $data = ['data' => $data];
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('get_input')) {
    function get_input()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
            return $_POST;
        }
        $raw = @file_get_contents('php://input');
        if (!$raw) return $_POST;
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }
}

if (!function_exists('e')) {
    function e($value, $flags = ENT_QUOTES)
    {
        if ($value === null) return '';
        return htmlspecialchars((string)$value, $flags, 'UTF-8');
    }
}

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

if (!function_exists('generate_csrf_token') || !function_exists('generate_csrf')) {
    function generate_csrf_token()
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

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
        global $pdo;
        if (!is_logged_in()) return null;
        $id   = get_current_user_id();
        $role = get_current_user_role();
        if (in_array($role, ['admin', 'super_admin', 'manager', 'editor'], true)) {
            $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            if ($u) $u['role'] = $u['role'] ?? 'admin';
            return $u;
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!is_logged_in()) {
            json_response([
                'success' => false,
                'error'   => 'Authentication required. Please log in.',
                'code'    => 'AUTH_REQUIRED',
            ], 401);
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($role)
    {
        require_login();
        $userRole   = get_current_user_role();
        $rolesArray = is_array($role) ? $role : [$role];
        $adminShortcuts = ['admin', 'super_admin', 'manager', 'editor'];
        $anyAdmin = array_intersect($rolesArray, ['admin']);
        if ($anyAdmin) {
            $rolesArray = array_values(array_unique(array_merge($rolesArray, $adminShortcuts)));
        }
        if (!in_array($userRole, $rolesArray, true)) {
            json_response([
                'success' => false,
                'error'   => 'Forbidden. Insufficient permissions.',
                'code'    => 'INSUFFICIENT_PERMISSION',
            ], 403);
        }
        return get_current_user_id();
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

if (!function_exists('create_notification')) {
    function create_notification($user_id, $type, $title, $message, $related_id = null, $related_type = null, $priority = 'normal', $data = null)
    {
        global $pdo;
        $recipientUserId = null;

        if (!empty($user_id)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$user_id]);
            if ($stmt->fetch()) {
                $recipientUserId = (int)$user_id;
            }
        }

        if ($recipientUserId === null) {
            $stmt = $pdo->prepare('SELECT id FROM users ORDER BY id LIMIT 1');
            $stmt->execute();
            $fallback = $stmt->fetch();
            if ($fallback) {
                $recipientUserId = (int)($fallback['id'] ?? 0);
            }
        }

        if (!$recipientUserId) {
            return null;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, type, title, message, data, related_id, related_type, priority, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ');
            $stmt->execute([
                $recipientUserId,
                $type,
                substr((string)$title, 0, 255),
                (string)$message,
                $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
                $related_id !== null ? (int)$related_id : null,
                $related_type,
                $priority,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('log_activity')) {
    function log_activity($user_id, $action, $entity_type = null, $entity_id = null, $details = null)
    {
        global $pdo;
        $role    = $_SESSION['role'] ?? null;
        $adminId = in_array($role, ['admin', 'super_admin', 'manager', 'editor'], true) ? $user_id : null;
        $userId  = $adminId ? null : $user_id;
        $ip      = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        if (is_array($ip)) $ip = null;
        if (is_string($ip) && strpos($ip, ',') !== false) $ip = explode(',', $ip)[0];
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $url     = ($_SERVER['REQUEST_URI'] ?? null);
        $method  = $_SERVER['REQUEST_METHOD'] ?? null;
        $description = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : (string)($details ?? '');
        try {
            $stmt = $pdo->prepare('
                INSERT INTO activity_logs (user_id, admin_user_id, action, module, subject_type, subject_id, description, old_values, new_values, ip_address, user_agent, url, method, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                $userId,
                $adminId,
                $action,
                $entity_type ?? 'system',
                $entity_type,
                is_numeric($entity_id) ? (int)$entity_id : null,
                $description,
                null,
                is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                is_string($ip) ? substr($ip, 0, 45) : null,
                is_string($ua) ? substr($ua, 0, 500) : null,
                is_string($url) ? substr($url, 0, 500) : null,
                $method,
            ]);
            return $pdo->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('send_email')) {
    function send_email($to, $subject, $body, $headers = [], $attachments = [])
    {
        $toEmails  = is_array($to) ? $to : [$to];
        $toLine    = implode(', ', array_filter($toEmails));
        $subject   = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $defaultH  = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . mb_encode_mimeheader(SMTP_FROM_NAME) . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion(),
        ];
        $mergedH = array_merge($defaultH, $headers);
        $headersString = implode("\r\n", $mergedH);

        if (SMTP_HOST !== 'localhost' && !empty(SMTP_USER)) {
            $mailerPath = __DIR__ . '/../helpers/../vendor/autoload.php';
            if (file_exists($mailerPath)) {
                try {
                    require_once $mailerPath;
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = (SMTP_ENCRYPTION === 'ssl') ? 'ssl' : 'tls';
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    foreach ($toEmails as $e) $mail->addAddress($e);
                    foreach ($attachments as $a) {
                        if (is_array($a)) $mail->addAttachment($a['path'] ?? '', $a['name'] ?? '');
                        else $mail->addAttachment($a);
                    }
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->AltBody = strip_tags($body);
                    return $mail->send() ? ['success' => true] : ['success' => false, 'error' => $mail->ErrorInfo];
                } catch (Throwable $e) {
                    return ['success' => false, 'error' => $e->getMessage()];
                }
            }
            ini_set('SMTP', SMTP_HOST);
            ini_set('smtp_port', SMTP_PORT);
            ini_set('sendmail_from', SMTP_FROM_EMAIL);
        }

        $ok = true;
        foreach ($toEmails as $email) {
            if (!@mail($email, $subject, $body, $headersString)) $ok = false;
        }
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'mail() returned false'];
    }
}

if (!function_exists('send_registration_pending_email')) {
    function send_registration_pending_email($email, $name, $extra = [])
    {
        $subject = 'Your Registration is Pending Review - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#1a2340'>Registration Received</h2>
        <p>Hello <strong>" . e($name) . "</strong>,</p>
        <p>Thank you for registering with " . SITE_NAME . ".</p>
        <p>Your application has been received and is currently <strong>under review</strong> by our admin team.
        We will contact you shortly if any additional details are required.</p>
        <p>Best regards,<br><strong>" . SITE_NAME . "</strong></p>
        </body></html>";
        return send_email($email, $subject, $body);
    }
}

if (!function_exists('send_registration_approved_email')) {
    function send_registration_approved_email($email, $name = 'User')
    {
        $subject = 'Your Registration Has Been Approved - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#16a34a'>Welcome Aboard!</h2>
        <p>Dear <strong>" . e($name) . "</strong>,</p>
        <p>Your registration has been <strong style='color:#16a34a'>APPROVED</strong>.</p>
        <p>You can now log in here: <a href='" . SITE_URL . "/login'>" . SITE_URL . "/login</a></p>
        <p>Thank you for joining " . SITE_NAME . ".</p>
        </body></html>";
        return send_email($email, $subject, $body);
    }
}

if (!function_exists('send_account_suspended_email')) {
    function send_account_suspended_email($email, $name, $reason)
    {
        $subject = 'Account Suspension Notice - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#dc2626'>Account Suspended</h2>
        <p>Hello <strong>" . e($name) . "</strong>,</p>
        <p>Your account has been <strong style='color:#dc2626'>SUSPENDED</strong> for the following reason:</p>
        <blockquote style='border-left:3px solid #dc2626;padding:8px 16px;background:#fef2f2'>" . nl2br(e($reason)) . "</blockquote>
        <p>For queries, reply to this email or contact <a href='mailto:" . SITE_EMAIL . "'>" . SITE_EMAIL . "</a>.</p>
        </body></html>";
        return send_email($email, $subject, $body);
    }
}

if (!function_exists('send_need_details_email')) {
    function send_need_details_email($email, $name, $message)
    {
        $subject = 'Additional Details Required - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#ca8a04'>Additional Information Required</h2>
        <p>Dear <strong>" . e($name) . "</strong>,</p>
        <p>To complete the review of your registration, we need the following additional details:</p>
        <blockquote style='border-left:3px solid #ca8a04;padding:8px 16px;background:#fffbeb'>" . nl2br(e($message)) . "</blockquote>
        <p>Please reply to this email with the requested information.</p>
        <p>Thank you,<br>" . SITE_NAME . "</p>
        </body></html>";
        return send_email($email, $subject, $body);
    }
}

if (!function_exists('send_requirement_status_email')) {
    function send_requirement_status_email($email, $name, $status, $requirement_id, $reason = '')
    {
        $statusText = ucfirst(strtolower($status));
        $color      = ($status === STATUS_APPROVED) ? '#16a34a' : (($status === STATUS_REJECTED || $status === STATUS_FAKE) ? '#dc2626' : '#ca8a04');
        $subject    = "Purchase Requirement #{$requirement_id} - {$statusText}";
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:" . $color . "'>Requirement #{$requirement_id}: {$statusText}</h2>
        <p>Dear <strong>" . e($name) . "</strong>,</p>
        <p>Your Purchase Requirement #{$requirement_id} has been <strong style='color:" . $color . "'>{$statusText}</strong>.</p>
        " . ($reason ? "<p><strong>Note:</strong> " . nl2br(e($reason)) . "</p>" : "") . "
        <p>Regards,<br>" . SITE_NAME . "</p>
        </body></html>";
        return send_email($email, $subject, $body);
    }
}

if (!function_exists('send_requirement_approved_email')) {
    function send_requirement_approved_email($data)
    {
        return send_requirement_status_email(
            $data['buyer_email'] ?? $data['email'],
            $data['buyer_name']  ?? $data['name'],
            STATUS_APPROVED,
            $data['requirement_id'] ?? $data['id']
        );
    }
}

if (!function_exists('send_requirement_rejected_email')) {
    function send_requirement_rejected_email($data)
    {
        return send_requirement_status_email(
            $data['buyer_email'] ?? $data['email'],
            $data['buyer_name']  ?? $data['name'],
            STATUS_REJECTED,
            $data['requirement_id'] ?? $data['id'],
            $data['reason'] ?? ''
        );
    }
}

if (!function_exists('send_product_published_email')) {
    function send_product_published_email($data)
    {
        $subject = 'Product Published: ' . ($data['product_name'] ?? '') . ' - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#16a34a'>Product Published</h2>
        <p>Hello <strong>" . e($data['seller_name'] ?? '') . "</strong>,</p>
        <p>Your product <strong>" . e($data['product_name'] ?? '') . "</strong> has been <strong>published</strong> and is now visible to buyers on " . SITE_NAME . ".</p>
        <p>Regards,<br>" . SITE_NAME . "</p>
        </body></html>";
        return send_email($data['seller_email'] ?? $data['email'] ?? '', $subject, $body);
    }
}

if (!function_exists('send_product_updated_email')) {
    function send_product_updated_email($data)
    {
        $subject = 'Product Updated: ' . ($data['product_name'] ?? '') . ' - ' . SITE_NAME;
        $body = "
        <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#222'>
        <h2 style='color:#2563eb'>Product Details Updated</h2>
        <p>Hello <strong>" . e($data['seller_name'] ?? '') . "</strong>,</p>
        <p>The following product has been <strong>updated</strong> by the admin team:</p>
        <p><strong>Product:</strong> " . e($data['product_name'] ?? '') . "</p>
        " . (!empty($data['changes']) ? "<p><strong>Changes:</strong><br>" . nl2br(e(is_array($data['changes']) ? implode("\n", $data['changes']) : $data['changes'])) . "</p>" : "") . "
        <p>Regards,<br>" . SITE_NAME . "</p>
        </body></html>";
        return send_email($data['seller_email'] ?? $data['email'] ?? '', $subject, $body);
    }
}

if (!function_exists('upload_file')) {
    function upload_file($file, $subdir = 'misc', $allowedTypes = null, $maxSize = null)
    {
        $allowedExt  = array_merge(ALLOWED_IMAGE_EXT, ALLOWED_DOC_EXT);
        $allowedMime = array_merge(ALLOWED_IMAGE_MIME, ALLOWED_DOC_MIME);
        if (is_array($allowedTypes) && $allowedTypes) {
            $sample = $allowedTypes[0] ?? '';
            if (strpos($sample, '/') !== false) $allowedMime = $allowedTypes;
            else $allowedExt = $allowedTypes;
        }
        $maxSize = $maxSize ?: UPLOAD_MAX_SIZE;

        if (!isset($file) || !is_array($file) || !isset($file['error'])) {
            return ['success' => false, 'error' => 'Invalid upload payload'];
        }
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL    => 'File upload was incomplete',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
        ];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $errMap[$file['error']] ?? 'Unknown upload error'];
        }
        if (empty($file['size']) || $file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB)'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return ['success' => false, 'error' => 'File extension not allowed: ' . implode(', ', $allowedExt)];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMime, true)) {
            return ['success' => false, 'error' => 'Invalid file content (MIME)'];
        }
        if ($ext === 'jpg') $ext = 'jpeg';
        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $monthDir = date('Y') . '/' . date('m') . '/';
        $targetDir = rtrim(UPLOAD_DIR, '/\\') . '/' . trim($subdir, '/\\') . '/' . $monthDir;
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                return ['success' => false, 'error' => 'Failed to create target directory'];
            }
        }
        $targetPath = $targetDir . $safeName;
        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        @chmod($targetPath, 0644);
        $relativeUrl = UPLOAD_URL . trim($subdir, '/\\') . '/' . $monthDir . $safeName;
        return [
            'success'  => true,
            'url'      => $relativeUrl,
            'path'     => $targetPath,
            'filename' => $safeName,
            'size'     => $file['size'],
            'mime'     => $mime,
        ];
    }
}
