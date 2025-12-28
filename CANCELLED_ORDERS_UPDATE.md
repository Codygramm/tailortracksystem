# Cancelled Orders - Exclusion from Payment Process

## Overview
Updated the payment processing system to completely exclude and prevent cancelled orders from being processed for payment.

## Changes Made

### File: `cashier/process_payment.php`

#### 1. **Excluded Cancelled Orders from Payment Queue**
**Line 122:** Updated the query to exclude cancelled orders from the "Awaiting Payment" list

**Before:**
```php
SELECT * FROM orders WHERE created_by = ? AND deposit_paid = 'yes' AND payment_status = 'pending'
```

**After:**
```php
SELECT * FROM orders WHERE created_by = ? AND deposit_paid = 'yes' AND payment_status = 'pending' AND status != 'cancel'
```

**Result:** Cancelled orders will no longer appear in the payment queue list.

---

#### 2. **Prevented Loading Cancelled Order Details**
**Line 22:** Updated order details loading to exclude cancelled orders

**Before:**
```php
SELECT * FROM orders WHERE order_id = ? AND created_by = ?
```

**After:**
```php
SELECT * FROM orders WHERE order_id = ? AND created_by = ? AND status != 'cancel'
```

**Result:** If someone tries to access a cancelled order via direct URL, they will see an error message.

**Error Message:** "Order not found, has been cancelled, or you don't have permission to access this order."

---

#### 3. **Added Payment Processing Validation**
**Lines 45-56:** Added validation before processing payment to prevent cancelled orders from being paid

```php
// First, verify the order exists and is not cancelled
$stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ? AND created_by = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_check = $stmt->fetch();

if (!$order_check) {
    throw new Exception("Order not found.");
}

if ($order_check['status'] === 'cancel') {
    throw new Exception("Cannot process payment for a cancelled order.");
}
```

**Result:** Even if someone tries to submit a payment form for a cancelled order, the system will reject it.

---

## How It Works

### Scenario 1: Viewing Payment Queue
**Before:** Cancelled orders appeared in the "Awaiting Payment" list
**After:** ✅ Cancelled orders are automatically filtered out and don't show up

### Scenario 2: Accessing Cancelled Order via URL
**Example:** `process_payment.php?order_id=ORD123` (where ORD123 is cancelled)

**Before:** Order details would load, allowing payment processing
**After:** ✅ Error message shown, order details not loaded

### Scenario 3: Attempting to Pay Cancelled Order
**Before:** Payment could be processed
**After:** ✅ Payment rejected with error: "Cannot process payment for a cancelled order."

---

## Security & Data Integrity

### Three Layers of Protection:
1. **Display Layer** - Cancelled orders don't appear in payment queue
2. **Load Layer** - Cancelled orders can't be loaded for payment
3. **Processing Layer** - Payment submission for cancelled orders is rejected

### Database Status Values:
- `'cancel'` - Order is cancelled (note: no 'led' at the end)
- Orders with `status = 'cancel'` are excluded from all payment operations

---

## Complete Order Cancellation Flow

When an order is cancelled from `manage_orders.php`:

1. **Status changed to 'cancel'**
2. **Order deleted from database** (along with measurements and receipts)
3. **Automatically removed from payment queue**
4. **Cannot be accessed for payment processing**
5. **Payment submission blocked if attempted**

---

## Testing Checklist

- [x] Cancelled orders don't appear in payment queue
- [x] Direct URL access to cancelled order shows error
- [x] Cannot submit payment for cancelled order
- [x] Error messages are user-friendly
- [x] No console errors or PHP warnings

---

## Related Files

- **cashier/process_payment.php** - Payment processing (UPDATED)
- **cashier/manage_orders.php** - Order cancellation and deletion
- **Database Table:** `orders` - Status field with 'cancel' value

---

**Date Updated:** December 22, 2025
**Updated By:** TailorTrack Development Team
