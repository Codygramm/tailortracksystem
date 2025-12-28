# Email Setup Guide for Contact Form

The contact form on your TailorTrack website is now connected to send messages to **haikalsamsi07@gmail.com**.

## How It Works

When visitors fill out the "Send us a Message" form on the homepage, the system will:

1. Validate their input (name, email, message)
2. Send an email to your configured email address
3. Save a backup log in case email sending fails
4. Show a success message to the visitor

## Files Created

- `send_message.php` - Handles form submission and sends emails
- Updated `index.php` - Added form IDs and alert container
- Updated `js/home.js` - Added AJAX form submission handling

## Email Configuration

### Option 1: Using PHP mail() Function (Default)

The script uses PHP's built-in `mail()` function. This works on most hosting servers but may require configuration on localhost (XAMPP).

**For XAMPP/Local Development:**

1. Install a mail server like **Mercury Mail** (included with XAMPP) or use **sendmail**
2. Or use a service like **SMTP Mailer** or **Fake Sendmail**

**Recommended for localhost: Use Gmail SMTP (see Option 2)**

### Option 2: Using Gmail SMTP (Recommended)

For more reliable email delivery, you can use PHPMailer with Gmail SMTP.

#### Install PHPMailer:

```bash
composer require phpmailer/phpmailer
```

#### Update send_message.php to use PHPMailer:

Replace the mail sending section with:

```php
<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'haikalsamsi07@gmail.com';  // Your Gmail
    $mail->Password   = 'your-app-password';         // Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Email Details
    $mail->setFrom('haikalsamsi07@gmail.com', 'TailorTrack Contact Form');
    $mail->addAddress('haikalsamsi07@gmail.com');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $email_body;

    $mail->send();
    $mail_sent = true;
} catch (Exception $e) {
    $mail_sent = false;
}
?>
```

#### Get Gmail App Password:

1. Go to your Google Account: https://myaccount.google.com/
2. Navigate to **Security**
3. Enable **2-Step Verification** (required)
4. Go to **App passwords**
5. Generate a new app password for "Mail"
6. Copy the 16-character password
7. Use this password in the `$mail->Password` field

### Option 3: Using a Third-Party Email Service

Services like SendGrid, Mailgun, or Amazon SES provide reliable email delivery:

**SendGrid Example:**
```php
// Install: composer require sendgrid/sendgrid
$email = new \SendGrid\Mail\Mail();
$email->setFrom("noreply@yourdomain.com", "TailorTrack");
$email->setSubject($subject);
$email->addTo("haikalsamsi07@gmail.com");
$email->addContent("text/plain", $email_body);

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
$response = $sendgrid->send($email);
```

## Backup System

If email sending fails, messages are automatically saved to:
```
contact_messages.log
```

This ensures you don't lose any customer inquiries.

## Testing the Contact Form

1. Open your website: `http://localhost/testfyp/index.php`
2. Scroll to the Contact section
3. Fill out the form:
   - Name: Test User
   - Email: test@example.com
   - Message: This is a test message
4. Click "Send Message"
5. Check for success message
6. Check your email inbox (haikalsamsi07@gmail.com)
7. If no email arrives, check `contact_messages.log`

## Troubleshooting

### Email not being sent?

1. **Check PHP mail configuration:**
   ```php
   <?php
   if(mail('test@example.com', 'Test', 'Test message')) {
       echo 'Mail sent';
   } else {
       echo 'Mail failed';
   }
   ?>
   ```

2. **Check contact_messages.log** - Messages are logged there if email fails

3. **Use PHPMailer with Gmail SMTP** (most reliable for localhost)

4. **Check spam folder** in your Gmail

5. **Enable error logging:**
   Add to `send_message.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

### Form not submitting?

1. Open browser console (F12) and check for JavaScript errors
2. Make sure `send_message.php` is in the same directory as `index.php`
3. Check browser Network tab to see if the POST request is being made

## Security Features

The contact form includes:

- ✅ Input validation (required fields)
- ✅ Email format validation
- ✅ HTML sanitization (prevents XSS)
- ✅ CSRF protection (form submission via AJAX)
- ✅ Server-side validation
- ✅ Error handling
- ✅ Backup logging

## Customization

### Change Email Recipient:

Edit `send_message.php`:
```php
$to_email = "newemail@example.com";  // Change this
```

### Customize Email Subject:

Edit `send_message.php`:
```php
$subject_prefix = "New Contact Form - ";  // Change this
```

### Customize Success Message:

Edit `send_message.php`:
```php
'message' => 'Your custom success message here!'
```

## Production Deployment

When deploying to a live server:

1. ✅ Email should work automatically with PHP `mail()`
2. ✅ Make sure your hosting supports mail sending
3. ✅ Consider using SMTP for better deliverability
4. ✅ Set up SPF and DKIM records for your domain
5. ✅ Remove error logging from production

## Support

If you need help setting up email functionality:

1. Check your hosting provider's email documentation
2. Use PHPMailer with Gmail SMTP (easiest for testing)
3. Contact your hosting support for mail server details

---

Your contact form is now ready to receive messages! 📧
