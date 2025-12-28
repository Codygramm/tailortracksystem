# Payment Method Update - Instructions

## Overview
The payment system has been updated to support three payment methods with receipt upload functionality for proof of payment.

## Changes Made

### 1. Database Updates
**File:** `update_payment_methods.sql`

Run this SQL script in phpMyAdmin to update the database:
```sql
-- Step 1: Update payment_method enum values
ALTER TABLE `receipts`
MODIFY COLUMN `payment_method` ENUM('cash','online_payment','qr_code') DEFAULT 'cash';

-- Step 2: Add receipt_file column
ALTER TABLE `receipts`
ADD COLUMN `receipt_file` VARCHAR(255) NULL AFTER `payment_method`;
```

### 2. Payment Methods
The system now supports:
- **Cash** - No receipt upload required
- **Online Payment** - Requires receipt upload
- **QR Code** - Requires receipt upload

### 3. File Upload
- **Location:** `/uploads/payment_receipts/`
- **Allowed Formats:** JPG, JPEG, PNG, PDF
- **Max File Size:** 5MB
- **Naming Convention:** `receipt_{order_id}_{timestamp}.{ext}`

### 4. Features Implemented

#### Process Payment Page (`cashier/process_payment.php`)
✅ Updated payment method dropdown
✅ Added file upload field (hidden by default)
✅ JavaScript to show/hide upload based on payment method
✅ Server-side file validation
✅ File upload handling with error management
✅ Receipt record insertion with file path
✅ Transaction rollback on upload failure

#### CSS Styling (`css/cashier.css`)
✅ Custom styling for upload section
✅ Dashed border with hover effects
✅ Styled file selector button
✅ Responsive design

#### Security
✅ Created `.htaccess` to prevent PHP execution in uploads folder
✅ File type validation (only images and PDF)
✅ File size validation (max 5MB)
✅ Unique filename generation to prevent overwrites

### 5. How It Works

**For Cashier:**
1. Select an order from the payment queue
2. Enter payment amount
3. Select payment method:
   - **Cash:** Proceed to confirm
   - **Online Payment/QR Code:** Upload receipt appears
4. If Online Payment or QR Code selected:
   - Upload field becomes mandatory
   - Select receipt file (image or PDF)
5. Click "Confirm Payment"
6. System validates and processes payment
7. Receipt file is stored and path saved to database

**Validation Rules:**
- Cash payment: No upload required
- Online Payment/QR Code: Upload is **mandatory**
- File must be JPG, PNG, or PDF
- File size must not exceed 5MB
- Missing upload for online/QR = Error message shown

### 6. Files Modified

1. **cashier/process_payment.php**
   - Added `enctype="multipart/form-data"` to form
   - Updated payment method options
   - Added receipt upload field
   - Added JavaScript for conditional display
   - Added PHP file upload handling
   - Added receipt record insertion

2. **css/cashier.css**
   - Added upload section styling (#receiptUploadSection)
   - Added file input button styling
   - Added hover effects

3. **Database**
   - Modified `receipts.payment_method` enum
   - Added `receipts.receipt_file` column

### 7. Directory Structure
```
testfyp/
├── cashier/
│   └── process_payment.php (Updated)
├── css/
│   └── cashier.css (Updated)
├── uploads/
│   └── payment_receipts/
│       ├── .htaccess (New - Security)
│       └── [uploaded receipts stored here]
├── update_payment_methods.sql (New)
└── PAYMENT_UPDATE_INSTRUCTIONS.md (This file)
```

### 8. Testing Checklist

- [ ] Run SQL update script
- [ ] Test Cash payment (no upload required)
- [ ] Test Online Payment (upload required)
- [ ] Test QR Code payment (upload required)
- [ ] Try uploading invalid file type (should fail)
- [ ] Try uploading file > 5MB (should fail)
- [ ] Verify file saved in `/uploads/payment_receipts/`
- [ ] Verify receipt record created in database
- [ ] Check file path stored correctly

### 9. Troubleshooting

**Upload folder permission denied:**
```bash
chmod 777 /path/to/testfyp/uploads/payment_receipts
```

**File not uploading:**
- Check `php.ini` settings:
  - `upload_max_filesize` >= 5MB
  - `post_max_size` >= 5MB
- Ensure upload directory exists and is writable

**Payment fails with upload:**
- Check browser console for JavaScript errors
- Check PHP error log for upload errors
- Verify database column `receipt_file` exists

## Next Steps (Optional)

Future enhancements you could add:
1. View/download uploaded receipt from order details
2. Receipt thumbnail preview before upload
3. Multiple receipt uploads for split payments
4. Receipt verification status (approved/rejected)
5. Automatic receipt number generation
6. Email receipt copy to customer

---

**Date Updated:** December 22, 2025
**Updated By:** TailorTrack Development Team
