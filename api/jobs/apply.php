<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = get_input();

$required = ['job_slug', 'name', 'email', 'phone'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        json_response(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $f)) . ' is required'], 400);
    }
}

$email = trim($input['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email format'], 400);
}

$resumePath = '';
if (!empty($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['resume'];
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        json_response(['success' => false, 'message' => 'Resume too large (max 5MB)'], 400);
    }
    $allowed = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        json_response(['success' => false, 'message' => 'Invalid resume file type (allowed: pdf, doc, docx, txt, rtf)'], 400);
    }
    if (!is_dir(UPLOAD_DIR . 'resumes/')) {
        @mkdir(UPLOAD_DIR . 'resumes/', 0777, true);
    }
    $fname = 'resume_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . 'resumes/' . $fname;
    if (@move_uploaded_file($file['tmp_name'], $dest)) {
        $resumePath = 'uploads/resumes/' . $fname;
    }
}

try {
    $jobTitle = trim($input['job_title'] ?? $input['job_slug'] ?? '');
    $jobId = trim($input['job_id'] ?? $input['job_slug'] ?? '');
    $fullName = trim($input['name'] ?? $input['full_name'] ?? '');
    $coverLetter = trim($input['cover_letter'] ?? $input['message'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO job_applications
        (job_title, job_id, full_name, email, phone, cover_letter, resume_path, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'received', NOW())");
    $stmt->execute([
        $jobTitle,
        $jobId,
        $fullName,
        $email,
        trim($input['phone']),
        $coverLetter,
        $resumePath,
    ]);
    $id = $pdo->lastInsertId();

    create_notification(null, 'review', 'New job application', 'A candidate has submitted a new job application.', $id, 'job_application', 'high', ['job' => $jobTitle]);
    log_activity(null, 'job_application_submitted', 'job_application', $id, ['job' => $jobTitle, 'email' => $email]);

    $body = '
    <html><body>
    <h2>New Job Application #' . $id . '</h2>
    <p><strong>Job:</strong> ' . e($jobTitle) . '<br>
    <strong>Name:</strong> ' . e($fullName) . '<br>
    <strong>Email:</strong> ' . e($email) . '<br>
    <strong>Phone:</strong> ' . e(trim($input['phone'])) . '</p>';
    if ($coverLetter !== '') {
        $body .= '<p><strong>Cover Letter:</strong><br>' . nl2br(e($coverLetter)) . '</p>';
    }
    if ($resumePath !== '') {
        $body .= '<p><strong>Resume:</strong> <a href="' . e($resumePath) . '">Download</a></p>';
    }
    $body .= '</body></html>';
    try { send_email(SITE_EMAIL, 'New Job Application: ' . e($jobTitle), $body); } catch (Throwable $e) {}

    $userBody = '
    <html><body>
    <h2>Application Received</h2>
    <p>Dear ' . e($fullName) . ',</p>
    <p>Thank you for applying for the ' . e($jobTitle) . ' position. We have received your application and will review it shortly.</p>
    <p>Best regards,<br>' . SITE_NAME . ' Team</p>
    </body></html>';
    try { send_email($email, 'Application Received - ' . SITE_NAME, $userBody); } catch (Throwable $e) {}

    json_response([
        'success' => true,
        'message' => 'Job application submitted successfully'
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to submit: ' . $e->getMessage()], 500);
}
