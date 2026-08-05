<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($to, $subject, $body, $attachments = [])
{
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        if (is_array($to)) {
            foreach ($to as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($to);
        }

        foreach ($attachments as $attachment) {
            if (is_array($attachment)) {
                $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
            } else {
                $mail->addAttachment($attachment);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

function email_template_registration_pending($data)
{
    return '
    <html><body>
    <h2>Registration Pending</h2>
    <p>Hello ' . htmlspecialchars($data['name']) . ',</p>
    <p>Your registration has been received and is currently under review.</p>
    <p>We will notify you once the review process is complete.</p>
    <p>Thank you for your patience.</p>
    </body></html>';
}

function email_template_need_more_details($data)
{
    return '
    <html><body>
    <h2>Additional Details Required</h2>
    <p>Hello ' . htmlspecialchars($data['name']) . ',</p>
    <p>We need additional details to process your registration:</p>
    <p>' . nl2br(htmlspecialchars($data['message'])) . '</p>
    <p>Please log in to your account to provide the requested information.</p>
    </body></html>';
}

function email_template_registration_approved($data)
{
    return '
    <html><body>
    <h2>Registration Approved</h2>
    <p>Hello ' . htmlspecialchars($data['name']) . ',</p>
    <p>Congratulations! Your registration has been approved.</p>
    <p>You can now log in and start using our services.</p>
    <p>Welcome aboard!</p>
    </body></html>';
}

function email_template_account_suspended($data)
{
    return '
    <html><body>
    <h2>Account Suspended</h2>
    <p>Hello ' . htmlspecialchars($data['name']) . ',</p>
    <p>Your account has been suspended for the following reason:</p>
    <p>' . nl2br(htmlspecialchars($data['reason'])) . '</p>
    <p>Please contact admin@example.com for further assistance.</p>
    </body></html>';
}

function email_template_product_published($data)
{
    return '
    <html><body>
    <h2>Product Published</h2>
    <p>Hello ' . htmlspecialchars($data['seller_name']) . ',</p>
    <p>Your product "' . htmlspecialchars($data['product_name']) . '" has been published successfully.</p>
    <p>It is now visible to buyers on our platform.</p>
    </body></html>';
}

function email_template_requirement_approved($data)
{
    return '
    <html><body>
    <h2>Requirement Approved</h2>
    <p>Hello ' . htmlspecialchars($data['buyer_name']) . ',</p>
    <p>Your requirement "' . htmlspecialchars($data['requirement_title']) . '" has been approved.</p>
    <p>Sellers can now view and respond to your requirement.</p>
    </body></html>';
}

function email_template_requirement_rejected($data)
{
    return '
    <html><body>
    <h2>Requirement Rejected</h2>
    <p>Hello ' . htmlspecialchars($data['buyer_name']) . ',</p>
    <p>Your requirement "' . htmlspecialchars($data['requirement_title']) . '" has been rejected.</p>
    <p>Reason: ' . nl2br(htmlspecialchars($data['reason'])) . '</p>
    <p>Please revise and resubmit your requirement.</p>
    </body></html>';
}
