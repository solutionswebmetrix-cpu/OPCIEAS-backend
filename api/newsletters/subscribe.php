<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();
$email = trim($input['email'] ?? '');

if (empty($email)) {
    json_response(['success' => false, 'message' => 'Email is required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM newsletters WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response([
            'success' => true,
            'message' => 'You are already subscribed to our newsletter'
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO newsletters (email, source, subscription_status, subscribed_at, created_at) VALUES (?, ?, 'subscribed', NOW(), NOW())");
    $stmt->execute([
        $email,
        trim($input['source'] ?? 'website_footer'),
    ]);

    $id = (int)$pdo->lastInsertId();
    create_notification(null, 'system', 'Newsletter subscription', 'A new newsletter subscriber joined the mailing list.', $id, 'newsletter', 'normal', ['email' => $email]);
    log_activity(null, 'newsletter_subscribed', 'newsletter', $id, ['email' => $email]);

    $body = '
    <html><body>
    <h2>Newsletter Subscription</h2>
    <p>Dear subscriber,</p>
    <p>Thank you for subscribing to the ' . SITE_NAME . ' newsletter. We will keep you updated with our latest news and offers.</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    try { send_email($email, 'Welcome to ' . SITE_NAME . ' Newsletter', $body); } catch (Throwable $e) {}

    json_response([
        'success' => true,
        'message' => 'Subscribed successfully'
    ]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), '1062 Duplicate') !== false || strpos($e->getMessage(), 'unique') !== false) {
        json_response([
            'success' => true,
            'message' => 'You are already subscribed to our newsletter'
        ]);
    }
    json_response(['success' => false, 'message' => 'Failed to subscribe: ' . $e->getMessage()], 500);
}
