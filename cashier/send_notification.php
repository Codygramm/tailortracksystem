<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Load Composer's autoloader for PHPMailer
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get POST data
$order_id = $_POST['order_id'] ?? '';
$customer_name = $_POST['customer_name'] ?? '';
$customer_email = $_POST['customer_email'] ?? '';
$total_amount = $_POST['total_amount'] ?? 0;
$amount_paid = $_POST['amount_paid'] ?? 0;

// Validate data
if (empty($order_id) || empty($customer_name) || empty($customer_email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required information']);
    exit();
}

// Calculate balance
$balance = floatval($total_amount) - floatval($amount_paid);

// Create email subject
$subject = "Your Order is Ready for Collection - " . $order_id;

// Create email body (HTML)
$email_body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #25344F 0%, #617891 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #D5B893;
            margin: 20px 0;
            border-radius: 5px;
        }
        .payment-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .payment-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .payment-table td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .total-row {
            background: #fff3cd;
            font-size: 1.2em;
            font-weight: bold;
        }
        .footer {
            background: #25344F;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            margin-top: 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #D5B893;
            color: #25344F;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .highlight {
            color: #D5B893;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin: 0;'>🎉 Your Order is Ready!</h1>
            <p style='margin: 10px 0 0 0; font-size: 1.1em;'>WARISAN EWAN NIAGA RESOURCES</p>
        </div>

        <div class='content'>
            <h2>Hello {$customer_name},</h2>

            <p>Great news! Your custom clothing order is now <strong>ready for collection</strong>.</p>

            <div class='info-box'>
                <h3 style='margin-top: 0;'>📦 Order Details</h3>
                <p><strong>Order ID:</strong> <span class='highlight'>{$order_id}</span></p>
                <p><strong>Status:</strong> Completed & Ready to Collect</p>
            </div>

            <h3>💰 Payment Information</h3>
            <table class='payment-table'>
                <tr>
                    <td>Total Amount:</td>
                    <td>RM " . number_format($total_amount, 2) . "</td>
                </tr>
                <tr>
                    <td>Amount Paid:</td>
                    <td>RM " . number_format($amount_paid, 2) . "</td>
                </tr>
                <tr class='total-row'>
                    <td>Balance Due:</td>
                    <td>RM " . number_format($balance, 2) . "</td>
                </tr>
            </table>

            " . ($balance > 0 ?
            "<div class='info-box' style='border-left-color: #dc3545;'>
                <p style='margin: 0;'><strong>⚠️ Please Note:</strong> You have an outstanding balance of <strong>RM " . number_format($balance, 2) . "</strong> to pay upon collection.</p>
            </div>" :
            "<div class='info-box' style='border-left-color: #28a745;'>
                <p style='margin: 0;'><strong>✅ Payment Complete:</strong> Your order is fully paid. No payment required upon collection.</p>
            </div>") . "

            <h3>📍 Collection Details</h3>
            <div class='info-box'>
                <p><strong>Location:</strong><br>
                WARISAN EWAN NIAGA RESOURCES<br>
                Jalan Taib 3, Pontian District, Johor</p>

                <p><strong>Operating Hours:</strong><br>
                Monday - Saturday: 9:00 AM - 6:00 PM<br>
                Sunday: 10:00 AM - 4:00 PM</p>

                <p><strong>What to Bring:</strong></p>
                <ul style='margin: 5px 0;'>
                    <li>Your Order ID: <strong>{$order_id}</strong></li>
                    <li>Photo identification (IC/Driver's License)</li>
                    " . ($balance > 0 ? "<li><strong>Payment for balance: RM " . number_format($balance, 2) . "</strong></li>" : "") . "
                </ul>
            </div>

            <p style='text-align: center;'>
                <a href='http://localhost/testfyp/tracking.php' class='btn'>Track Your Order Online</a>
            </p>

            <p style='margin-top: 30px;'>If you have any questions, please don't hesitate to contact us at:</p>
            <p style='margin: 5px 0;'>
                📞 Phone: +60 12-345 6789<br>
                ✉️ Email: haikalsamsi07@gmail.com
            </p>

            <p style='margin-top: 20px;'>We look forward to seeing you soon!</p>

            <p>Best regards,<br>
            <strong>WARISAN EWAN NIAGA RESOURCES Team</strong></p>
        </div>

        <div class='footer'>
            <p style='margin: 0; font-size: 0.9em;'>This is an automated notification from TailorTrack</p>
            <p style='margin: 5px 0 0 0; font-size: 0.8em;'>Please do not reply to this email</p>
        </div>
    </div>
</body>
</html>
";

// Plain text version
$email_body_plain = "
Hello {$customer_name},

Great news! Your custom clothing order is now ready for collection.

ORDER DETAILS
=============
Order ID: {$order_id}
Status: Completed & Ready to Collect

PAYMENT INFORMATION
===================
Total Amount: RM " . number_format($total_amount, 2) . "
Amount Paid: RM " . number_format($amount_paid, 2) . "
Balance Due: RM " . number_format($balance, 2) . "

" . ($balance > 0 ?
"⚠️ IMPORTANT: You have an outstanding balance of RM " . number_format($balance, 2) . " to pay upon collection." :
"✅ Your order is fully paid. No payment required upon collection.") . "

COLLECTION DETAILS
==================
Location:
WARISAN EWAN NIAGA RESOURCES
Jalan Taib 3, Pontian District, Johor

Operating Hours:
Monday - Saturday: 9:00 AM - 6:00 PM
Sunday: 10:00 AM - 4:00 PM

What to Bring:
- Your Order ID: {$order_id}
- Photo identification (IC/Driver's License)
" . ($balance > 0 ? "- Payment for balance: RM " . number_format($balance, 2) : "") . "

If you have any questions, please contact us:
Phone: +60 12-345 6789
Email: haikalsamsi07@gmail.com

We look forward to seeing you soon!

Best regards,
WARISAN EWAN NIAGA RESOURCES Team

---
This is an automated notification from TailorTrack
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
    $mail->Password   = 'obpvtappkcezvxnz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('haikalsamsi07@gmail.com', 'WARISAN EWAN NIAGA RESOURCES');
    $mail->addAddress($customer_email, $customer_name);
    $mail->addReplyTo('haikalsamsi07@gmail.com', 'WARISAN EWAN NIAGA RESOURCES');

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $email_body;
    $mail->AltBody = $email_body_plain;

    // Send email
    $mail->send();
    $mail_sent = true;

    // Log notification
    try {
        $log_stmt = $pdo->prepare("INSERT INTO notification_log (order_id, customer_email, notification_type, sent_by, sent_at) VALUES (?, ?, 'email', ?, NOW())");
        $log_stmt->execute([$order_id, $customer_email, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Log error but don't fail the notification
        error_log("Notification log error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Email notification sent successfully to ' . $customer_email
    ]);

} catch (Exception $e) {
    error_log("PHPMailer Error: {$mail->ErrorInfo}");

    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email: ' . $mail->ErrorInfo
    ]);
}
?>
