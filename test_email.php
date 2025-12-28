<?php
/**
 * Email Configuration Test Script
 * Use this to test if your PHPMailer setup is working correctly
 */

// Load Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Test - TailorTrack</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #25344F; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #25344F; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>📧 TailorTrack Email Configuration Test</h1>";

// Initialize PHPMailer
$mail = new PHPMailer(true);

try {
    echo "<div class='info'><strong>Step 1:</strong> Configuring SMTP settings...</div>";

    // Server settings
    $mail->SMTPDebug = 0; // Set to 2 for detailed debug output
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'haikalsamsi07@gmail.com';
    $mail->Password   = 'obpvtappkcezvxnz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    echo "<div class='success'>✓ SMTP settings configured</div>";

    echo "<div class='info'><strong>Step 2:</strong> Setting up email content...</div>";

    // Recipients
    $mail->setFrom('haikalsamsi07@gmail.com', 'TailorTrack System');
    $mail->addAddress('haikalsamsi07@gmail.com', 'WARISAN EWAN NIAGA RESOURCES');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from TailorTrack System';
    $mail->Body    = '
        <h2>Email Configuration Test</h2>
        <p>This is a test email from your TailorTrack contact form system.</p>
        <p><strong>If you receive this email, your PHPMailer configuration is working correctly!</strong></p>
        <hr>
        <p><small>Sent at: ' . date('Y-m-d H:i:s') . '</small></p>
        <p><small>From: TailorTrack Contact Form System</small></p>
    ';
    $mail->AltBody = 'This is a test email from your TailorTrack contact form system. If you receive this email, your PHPMailer configuration is working correctly!';

    echo "<div class='success'>✓ Email content prepared</div>";

    echo "<div class='info'><strong>Step 3:</strong> Sending email...</div>";

    // Send email
    $mail->send();

    echo "<div class='success'>
        <h3>✅ Email Sent Successfully!</h3>
        <p>Check your inbox at <strong>haikalsamsi07@gmail.com</strong></p>
        <p>Subject: <strong>{$mail->Subject}</strong></p>
        <p>The email may take a few seconds to arrive. Check your spam folder if you don't see it in your inbox.</p>
    </div>";

    echo "<div class='info'>
        <h4>Configuration Details:</h4>
        <ul>
            <li><strong>SMTP Host:</strong> smtp.gmail.com</li>
            <li><strong>Port:</strong> 587 (STARTTLS)</li>
            <li><strong>Username:</strong> haikalsamsi07@gmail.com</li>
            <li><strong>Authentication:</strong> ✓ Enabled</li>
            <li><strong>From Email:</strong> haikalsamsi07@gmail.com</li>
            <li><strong>To Email:</strong> haikalsamsi07@gmail.com</li>
        </ul>
    </div>";

} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Email Sending Failed</h3>
        <p><strong>Error Message:</strong> {$mail->ErrorInfo}</p>
    </div>";

    echo "<div class='info'>
        <h4>Troubleshooting Steps:</h4>
        <ol>
            <li>Verify your Gmail App Password is correct: <code>obpv tapp kcez vxnz</code> (without spaces)</li>
            <li>Make sure 2-Step Verification is enabled on your Google Account</li>
            <li>Check if 'Less secure app access' is disabled (you should use App Passwords instead)</li>
            <li>Verify your internet connection is working</li>
            <li>Check if your firewall is blocking port 587</li>
            <li>Try regenerating a new App Password from Google</li>
        </ol>
        <p><a href='https://myaccount.google.com/apppasswords' target='_blank' class='btn'>Generate New App Password</a></p>
    </div>";

    echo "<div class='error'>
        <h4>Debug Information:</h4>
        <pre>" . htmlspecialchars(print_r($e, true)) . "</pre>
    </div>";
}

echo "
    <hr>
    <div class='info'>
        <h4>Next Steps:</h4>
        <ul>
            <li>If the email was sent successfully, your contact form is ready to use!</li>
            <li>Go to your homepage and test the 'Send us a Message' form</li>
            <li>You can delete this test file after successful testing</li>
        </ul>
        <a href='index.php#contact' class='btn'>Test Contact Form</a>
        <a href='index.php' class='btn' style='background: #617891;'>Go to Homepage</a>
    </div>
</body>
</html>";
?>
