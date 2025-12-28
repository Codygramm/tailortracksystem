<?php
// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Load Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
$to_email = "haikalsamsi07@gmail.com";
$subject_prefix = "TailorTrack Contact Form - ";

// Get and sanitize form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (empty($message)) {
    $errors[] = "Message is required";
}

// If there are validation errors, return them
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// Sanitize data
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email_clean = filter_var($email, FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Create email subject
$subject = $subject_prefix . "Message from " . $name;

// Create email body
$email_body = "
===========================================
New Contact Form Submission
===========================================

From: {$name}
Email: {$email}
Date: " . date('Y-m-d H:i:s') . "

Message:
-------------------------------------------
{$message}
-------------------------------------------

===========================================
Sent from TailorTrack Contact Form
WARISAN EWAN NIAGA RESOURCES
===========================================
";

// Initialize PHPMailer
$mail = new PHPMailer(true);
$mail_sent = false;

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'haikalsamsi07@gmail.com';
    $mail->Password   = 'obpvtappkcezvxnz'; // Remove spaces from app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('haikalsamsi07@gmail.com', 'TailorTrack Contact Form');
    $mail->addAddress('haikalsamsi07@gmail.com', 'WARISAN EWAN NIAGA RESOURCES');
    $mail->addReplyTo($email_clean, $name);

    // Content
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $email_body;

    // Send email
    $mail->send();
    $mail_sent = true;

} catch (Exception $e) {
    // Log error for debugging
    error_log("PHPMailer Error: {$mail->ErrorInfo}");

    // Save to log file as backup
    $log_file = __DIR__ . '/contact_messages.log';
    $log_entry = "\n\n" . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "Name: {$name}\n";
    $log_entry .= "Email: {$email}\n";
    $log_entry .= "Message: {$message}\n";
    $log_entry .= "Error: {$mail->ErrorInfo}\n";
    $log_entry .= "-------------------------------------------\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Return JSON response
header('Content-Type: application/json');
if ($mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Your message has been received and logged. We will contact you soon.',
        'note' => 'Message saved to log file.'
    ]);
}
?>
