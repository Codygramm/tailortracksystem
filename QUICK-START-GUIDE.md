# Quick Start Guide - Email Testing

## ✅ Your PHPMailer is Now Configured!

I've set up your contact form to use PHPMailer with Gmail SMTP.

### Configuration Details:
- **Email:** haikalsamsi07@gmail.com
- **SMTP Server:** smtp.gmail.com
- **Port:** 587 (TLS)
- **App Password:** obpvtappkcezvxnz ✓

---

## 🧪 Step 1: Test Email Configuration

1. **Make sure XAMPP Apache is running**

2. **Open your browser and go to:**
   ```
   http://localhost/testfyp/test_email.php
   ```

3. **What you should see:**
   - ✅ Green success messages if email is sent
   - ❌ Red error messages if there's a problem

4. **Check your email:**
   - Open Gmail: haikalsamsi07@gmail.com
   - Look for test email (check spam if not in inbox)
   - Subject: "Test Email from TailorTrack System"

---

## 📧 Step 2: Test Contact Form

1. **Go to your homepage:**
   ```
   http://localhost/testfyp/index.php
   ```

2. **Scroll to the Contact section** at the bottom

3. **Fill out the form:**
   - Name: Test Customer
   - Email: test@example.com
   - Message: This is a test message from the contact form

4. **Click "Send Message"**

5. **You should see:**
   - ✅ Green success alert
   - Form fields cleared
   - Message: "Thank you for your message! We will get back to you soon."

6. **Check your email (haikalsamsi07@gmail.com):**
   - Look for: "TailorTrack Contact Form - Message from Test Customer"
   - The email will contain the customer's name, email, and message

---

## 🎯 What Happens When Someone Contacts You

### Email Format You'll Receive:

```
Subject: TailorTrack Contact Form - Message from [Customer Name]

===========================================
New Contact Form Submission
===========================================

From: John Doe
Email: customer@email.com
Date: 2023-12-19 14:30:00

Message:
-------------------------------------------
I would like to order a Baju Melayu...
-------------------------------------------

===========================================
Sent from TailorTrack Contact Form
WARISAN EWAN NIAGA RESOURCES
===========================================
```

### To Reply to Customer:
Just click "Reply" in your Gmail - it will automatically reply to the customer's email!

---

## 🔧 Troubleshooting

### If Test Email Fails:

1. **Check the error message** on test_email.php page

2. **Common Issues:**

   **"SMTP connect() failed"**
   - Check your internet connection
   - Verify firewall isn't blocking port 587

   **"Invalid password"**
   - Make sure App Password is entered without spaces
   - Try regenerating a new App Password:
     https://myaccount.google.com/apppasswords

   **"Could not authenticate"**
   - Verify 2-Step Verification is enabled on your Google Account
   - Generate a fresh App Password

3. **Need a new App Password?**
   - Go to: https://myaccount.google.com/
   - Click: Security → 2-Step Verification → App passwords
   - Generate new password
   - Update in: `send_message.php` line 89

### If Contact Form Shows Error:

1. **Open Browser Console** (Press F12)
   - Look for JavaScript errors
   - Check Network tab for failed requests

2. **Check PHP Errors:**
   - Look in: `contact_messages.log` file
   - Messages are saved here if email fails

---

## 🗑️ Clean Up After Testing

Once everything works, you can delete:
- ✓ `test_email.php` (test script)
- ✓ `contact_messages.log` (test logs, if any)

**Keep these files:**
- ✓ `send_message.php` (handles form submission)
- ✓ `vendor/` folder (PHPMailer library)
- ✓ `composer.json` and `composer.lock`

---

## 📱 Going Live (Production Deployment)

When you deploy to a live server:

1. ✅ The email configuration will work as-is
2. ✅ No changes needed to the code
3. ✅ Gmail SMTP works from any server
4. ⚠️ **Security Note:** Consider moving credentials to environment variables

### Secure Configuration (Optional):

Create a `.env` file:
```
SMTP_USERNAME=haikalsamsi07@gmail.com
SMTP_PASSWORD=obpvtappkcezvxnz
```

Then update send_message.php to use environment variables.

---

## 🎉 You're All Set!

Your contact form is now fully functional and will send emails to **haikalsamsi07@gmail.com**.

### Quick Links:
- 🏠 [Homepage](http://localhost/testfyp/index.php)
- 📧 [Test Email](http://localhost/testfyp/test_email.php)
- 📋 [Track Orders](http://localhost/testfyp/tracking.php)
- ❓ [FAQ](http://localhost/testfyp/faq.php)

---

## 📞 Need Help?

If you encounter any issues:
1. Check `contact_messages.log` for error details
2. Run the test email script again
3. Verify your Google App Password is still valid

**Important:** Keep your App Password secure - never share it publicly!
