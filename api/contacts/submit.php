<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, company, subject, message, type, priority, status, assigned_to, created_at FROM contacts ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $items = [];
        foreach ($rows as $row) {
            $status = trim((string)($row['status'] ?? ''));
            $items[] = [
                'id' => (string)($row['id'] ?? ''),
                'name' => $row['name'] ?? '',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? null,
                'company' => $row['company'] ?? null,
                'subject' => $row['subject'] ?? '',
                'message' => $row['message'] ?? '',
                'type' => $row['type'] ?? 'General',
                'priority' => $row['priority'] ?? 'Medium',
                'is_read' => false,
                'is_resolved' => in_array(strtolower($status), ['resolved', 'closed'], true),
                'assigned_to' => $row['assigned_to'] ?? null,
                'created_at' => $row['created_at'] ?? null,
            ];
        }
        json_response(['success' => true, 'data' => $items]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Failed to load contacts: ' . $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$required = ['name', 'email', 'subject', 'message'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst($f) . ' is required'], 400);
    }
}

$email = trim($input['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO contacts 
        (name, email, phone, company, subject, message, type, source, priority, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())");
    $stmt->execute([
        trim($input['name']),
        $email,
        trim($input['phone'] ?? ''),
        trim($input['company'] ?? ''),
        trim($input['subject']),
        trim($input['message']),
        trim($input['type'] ?? 'general'),
        trim($input['source'] ?? 'website'),
        trim($input['priority'] ?? 'normal'),
    ]);
    $id = $pdo->lastInsertId();

    create_notification(null, 'message', 'New contact submission', 'A new contact or inquiry message was received from the website.', $id, 'contact', 'high', [
        'name' => trim($input['name']),
        'email' => $email,
        'subject' => trim($input['subject']),
    ]);
    log_activity(null, 'contact_submitted', 'contact', $id, [
        'name' => trim($input['name']),
        'email' => $email,
        'subject' => trim($input['subject']),
    ]);

    $body = '
    <html><body>
    <h2>New Contact Form Submission #' . $id . '</h2>
    <p><strong>Name:</strong> ' . e(trim($input['name'])) . '<br>
    <strong>Email:</strong> ' . e($email) . '<br>
    <strong>Phone:</strong> ' . e(trim($input['phone'] ?? '')) . '<br>
    <strong>Company:</strong> ' . e(trim($input['company'] ?? '')) . '<br>
    <strong>Subject:</strong> ' . e(trim($input['subject'])) . '</p>
    <p><strong>Message:</strong><br>' . nl2br(e(trim($input['message']))) . '</p>
    </body></html>';
    try { send_email(SITE_EMAIL, 'Contact Form: ' . e(trim($input['subject'])), $body); } catch (Throwable $e) {}

    $userBody = '
    <html><body>
    <h2>Thank you for contacting us</h2>
    <p>Dear ' . e(trim($input['name'])) . ',</p>
    <p>We have received your message. Our team will get back to you shortly.</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    try { send_email($email, 'Thank you for contacting ' . SITE_NAME, $userBody); } catch (Throwable $e) {}

    json_response([
        'success' => true,
        'message' => 'Contact form submitted successfully'
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to submit: ' . $e->getMessage()], 500);
}
