<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';
$payment_data = [];
$order_details = [];

// Get order_id from URL if provided
$order_id = $_GET['order_id'] ?? '';

// Load order details if order_id is provided
if ($order_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND created_by = ? AND status != 'cancel'");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order_details = $stmt->fetch();

        if (!$order_details) {
            $error = "Order not found, has been cancelled, or you don't have permission to access this order.";
            $order_id = null; // Clear order_id if not found or cancelled
        }
    } catch (PDOException $e) {
        error_log("Fetch order error: " . $e->getMessage());
        $error = "Unable to load order details.";
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $order_id = $_POST['order_id'];
    $amount_paid = $_POST['amount_paid'];
    $payment_method = $_POST['payment_method'];
    $payment_notes = $_POST['payment_notes'] ?? '';
    $receipt_file_path = null;

    try {
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

        // Handle file upload for online_payment and qr_code
        if (($payment_method === 'online_payment' || $payment_method === 'qr_code') && isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/payment_receipts/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_tmp = $_FILES['receipt_file']['tmp_name'];
            $file_name = $_FILES['receipt_file']['name'];
            $file_size = $_FILES['receipt_file']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and PDF are allowed.");
            }

            if ($file_size > $max_file_size) {
                throw new Exception("File size exceeds 5MB limit.");
            }

            // Generate unique filename
            $new_filename = 'receipt_' . $order_id . '_' . time() . '.' . $file_ext;
            $receipt_file_path = $upload_dir . $new_filename;

            // Move uploaded file
            if (!move_uploaded_file($file_tmp, $receipt_file_path)) {
                throw new Exception("Failed to upload receipt file.");
            }
        } elseif (($payment_method === 'online_payment' || $payment_method === 'qr_code') && (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK)) {
            throw new Exception("Receipt file is required for Online Payment and QR Code methods.");
        }

        $pdo->beginTransaction();

        // Get the current deposit amount to calculate total amount paid
        $stmt = $pdo->prepare("SELECT deposit_amount FROM orders WHERE order_id = ? AND created_by = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order_info = $stmt->fetch();
        $deposit_amount = floatval($order_info['deposit_amount'] ?? 0);

        // Calculate total amount paid (deposit + current payment)
        $total_amount_paid = $deposit_amount + $amount_paid;

        // Update order payment status
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', amount_paid = ?, status = 'paid', updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND created_by = ?");
        $stmt->execute([$total_amount_paid, $order_id, $_SESSION['user_id']]);

        // Insert payment receipt record
        $receipt_number = 'PYMT-' . $order_id . '-' . time();
        $stmt = $pdo->prepare("INSERT INTO receipts (order_id, receipt_number, receipt_type, amount, payment_method, receipt_file, created_by) VALUES (?, ?, 'full_payment', ?, ?, ?, ?)");
        $stmt->execute([$order_id, $receipt_number, $amount_paid, $payment_method, $receipt_file_path, $_SESSION['user_id']]);

        // Prepare payment receipt data
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment_data = $stmt->fetch();

        $payment_data['payment_method'] = $payment_method;
        $payment_data['payment_notes'] = $payment_notes;
        $payment_data['payment_date'] = date('Y-m-d H:i:s');
        $payment_data['cashier_name'] = $_SESSION['full_name'];
        $payment_data['receipt_file'] = $receipt_file_path;

        $pdo->commit();
        $success = "Payment processed successfully!";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Delete uploaded file if transaction fails
        if ($receipt_file_path && file_exists($receipt_file_path)) {
            unlink($receipt_file_path);
        }
        error_log("Process payment error: " . $e->getMessage());
        $error = "Unable to process payment: " . $e->getMessage();
    }
}

// Get orders with deposit paid awaiting final payment (exclude cancelled orders)
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE created_by = ? AND deposit_paid = 'yes' AND payment_status = 'pending' AND status != 'cancel' ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $awaiting_payment = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch awaiting payment error: " . $e->getMessage());
    $error = "Unable to load orders data.";
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/cashier.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark cashier-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../Asset/logo_icon.png" alt="TailorTrack" style="width: 30px; height: 30px; object-fit: contain;" class="me-2">
                <span>TailorTrack</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cashierNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="cashierNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="take_order.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                Take Order
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="assign_order.php">
                                <i class="fas fa-user-check me-2"></i>
                                Assign Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Manage Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="process_payment.php">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Process Payment
                            </a>
                        </li>
                    </ul>
                    
                    <div class="sidebar-footer mt-4">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <div class="user-details">
                                <h6><?php echo $_SESSION['full_name']; ?></h6>
                                <span class="badge bg-cashier">Cashier</span>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Process Payment</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Orders
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Orders Awaiting Payment -->
                    <div class="col-lg-5 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Awaiting Payment
                                </h5>
                                <span class="badge bg-warning"><?php echo count($awaiting_payment); ?> orders</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($awaiting_payment)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                        <h5>All payments processed!</h5>
                                        <p class="text-muted">No orders awaiting payment.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="orders-list">
                                        <?php foreach ($awaiting_payment as $order): ?>
                                            <div class="order-item mb-3 p-3 border rounded <?php echo $order_id == $order['order_id'] ? 'border-primary bg-light' : ''; ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo $order['order_id']; ?></h6>
                                                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                                                        <p class="text-muted small mb-1"><?php echo str_replace('_', ' ', $order['order_type']); ?></p>
                                                        <p class="mb-0"><strong>RM <?php echo number_format($order['total_amount'], 2); ?></strong></p>
                                                    </div>
                                                    <div class="text-end">
                                                        <a href="process_payment.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-money-bill-wave me-1"></i> Process
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="col-lg-7 mb-4">
                        <?php if (empty($payment_data) && $order_details): ?>
                        <!-- Payment Processing Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-credit-card me-2"></i>Process Payment
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Order Summary -->
                                <div class="border rounded p-3 bg-light mb-4">
                                    <h6>Order Summary</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Order ID:</strong> <?php echo $order_details['order_id']; ?></p>
                                            <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                                            <p class="mb-1"><strong>Phone:</strong> <?php echo $order_details['customer_phone']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Order Type:</strong> <?php echo str_replace('_', ' ', $order_details['order_type']); ?></p>
                                            <p class="mb-1"><strong>Total Amount:</strong> RM <?php echo number_format($order_details['total_amount'], 2); ?></p>
                                            <?php if ($order_details['deposit_amount'] > 0): ?>
                                            <p class="mb-1 text-success"><strong>Deposit Paid:</strong> RM <?php echo number_format($order_details['deposit_amount'], 2); ?></p>
                                            <p class="mb-0 text-danger"><strong>Balance Due:</strong> RM <?php echo number_format($order_details['total_amount'] - $order_details['deposit_amount'], 2); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="process_payment.php" id="paymentForm" enctype="multipart/form-data">
                                    <input type="hidden" name="order_id" value="<?php echo $order_details['order_id']; ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="amount_paid" class="form-label">Amount to Pay (RM) *</label>
                                            <input type="number" class="form-control" id="amount_paid" name="amount_paid"
                                                   value="<?php echo number_format($order_details['total_amount'] - $order_details['deposit_amount'], 2, '.', ''); ?>" step="0.01" min="0" required>
                                            <small class="text-muted">Balance due: RM <?php echo number_format($order_details['total_amount'] - $order_details['deposit_amount'], 2); ?></small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="payment_method" class="form-label">Payment Method *</label>
                                            <select class="form-select" id="payment_method" name="payment_method" required>
                                                <option value="">Select Method</option>
                                                <option value="cash">Cash</option>
                                                <option value="online_payment">Online Payment</option>
                                                <option value="qr_code">QR Code</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Receipt Upload (Show only for Online Payment and QR Code) -->
                                    <div class="mb-3" id="receiptUploadSection" style="display: none;">
                                        <label for="receipt_file" class="form-label">
                                            <i class="fas fa-receipt me-2"></i>Upload Payment Receipt (Proof) *
                                        </label>
                                        <input type="file" class="form-control" id="receipt_file" name="receipt_file" accept="image/*,.pdf">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Accepted formats: JPG, PNG, PDF (Max 5MB). Required for Online Payment and QR Code.
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_notes" class="form-label">Payment Notes (Optional)</label>
                                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Any additional notes about the payment..."></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg" name="process_payment">
                                            <i class="fas fa-check-circle me-2"></i> Confirm Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php elseif (!empty($payment_data)): ?>
                        <!-- Payment Receipt -->
                        <div class="receipt-container">
                            <div class="receipt">
                                <!-- Company Header -->
                                <div class="receipt-header">
                                    <div class="company-logo">
                                        <img src="../Asset/logo_receipt.png" alt="Company Logo" class="receipt-logo">
                                    </div>
                                    <h2 class="company-name">WARISAN EWAN NIAGA RESOURCES</h2>
                                    <p class="company-address">Jalan Taib 3, Pontian District, Johor</p>
                                    <p class="company-contact">Tel: 012-345 6789 | Email: info@tailortrack.com</p>
                                    <div class="receipt-type-badge">
                                        <span class="badge-payment">PAYMENT RECEIPT</span>
                                    </div>
                                </div>

                                <!-- Receipt Info Bar -->
                                <div class="receipt-info-bar">
                                    <div class="info-item">
                                        <span class="info-label">Receipt No:</span>
                                        <span class="info-value"><strong>PYMT-<?php echo $payment_data['order_id']; ?></strong></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date:</span>
                                        <span class="info-value"><?php echo date('d M Y, g:i A', strtotime($payment_data['payment_date'])); ?></span>
                                    </div>
                                </div>

                                <!-- Customer Details Section -->
                                <div class="receipt-section">
                                    <h6 class="section-title">CUSTOMER DETAILS</h6>
                                    <table class="details-table">
                                        <tr>
                                            <td class="label-col">Order ID:</td>
                                            <td class="value-col"><strong><?php echo $payment_data['order_id']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">Customer Name:</td>
                                            <td class="value-col"><?php echo htmlspecialchars($payment_data['customer_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">Phone Number:</td>
                                            <td class="value-col"><?php echo htmlspecialchars($payment_data['customer_phone']); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Order Details Section -->
                                <div class="receipt-section">
                                    <h6 class="section-title">ORDER DETAILS</h6>
                                    <table class="details-table">
                                        <tr>
                                            <td class="label-col">Order Type:</td>
                                            <td class="value-col"><?php echo str_replace('_', ' ', ucwords($payment_data['order_type'])); ?></td>
                                        </tr>
                                        <?php if (!empty($payment_data['repair_type'])): ?>
                                        <tr>
                                            <td class="label-col">Repair Type:</td>
                                            <td class="value-col"><?php echo ucfirst($payment_data['repair_type']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="label-col">Payment Method:</td>
                                            <td class="value-col"><?php echo ucwords(str_replace('_', ' ', $payment_data['payment_method'])); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Payment Summary Section -->
                                <div class="receipt-section payment-section">
                                    <h6 class="section-title">PAYMENT SUMMARY</h6>
                                    <table class="payment-table">
                                        <tr>
                                            <td class="label-col">Total Order Amount:</td>
                                            <td class="amount-col">RM <?php echo number_format($payment_data['total_amount'], 2); ?></td>
                                        </tr>
                                        <?php if ($payment_data['deposit_amount'] > 0): ?>
                                        <tr class="deposit-row">
                                            <td class="label-col">Deposit Previously Paid:</td>
                                            <td class="amount-col">RM <?php echo number_format($payment_data['deposit_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">Balance Due:</td>
                                            <td class="amount-col">RM <?php echo number_format($payment_data['total_amount'] - $payment_data['deposit_amount'], 2); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr class="payment-row">
                                            <td class="label-col">Amount Paid Now:</td>
                                            <td class="amount-col">RM <?php echo number_format($payment_data['amount_paid'], 2); ?></td>
                                        </tr>
                                        <?php if ($payment_data['amount_paid'] > ($payment_data['total_amount'] - $payment_data['deposit_amount'])): ?>
                                        <tr>
                                            <td class="label-col">Change:</td>
                                            <td class="amount-col">RM <?php echo number_format($payment_data['amount_paid'] - ($payment_data['total_amount'] - $payment_data['deposit_amount']), 2); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr class="total-row">
                                            <td class="label-col"><strong>Payment Status:</strong></td>
                                            <td class="amount-col status-paid"><strong>PAID IN FULL</strong></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Footer -->
                                <div class="receipt-footer">
                                    <div class="footer-divider"></div>
                                    <?php if (!empty($payment_data['payment_notes'])): ?>
                                    <div class="footer-notes">
                                        <p><i class="fas fa-sticky-note"></i> <strong>Notes:</strong> <?php echo htmlspecialchars($payment_data['payment_notes']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="footer-notes">
                                        <p><i class="fas fa-check-circle"></i> <strong>Order Complete:</strong> Payment received in full. Order is ready for pickup.</p>
                                    </div>
                                    <div class="footer-thank-you">
                                        <p>Thank you for choosing our services!</p>
                                        <p class="signature-line">___________________________</p>
                                        <p class="signature-label">Cashier: <?php echo $payment_data['cashier_name']; ?></p>
                                    </div>
                                    <div class="footer-powered">
                                        <small>Powered by TailorTrack System</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary me-2" onclick="printReceipt()">
                                <i class="fas fa-print me-1"></i> Print Receipt
                            </button>
                            <button class="btn btn-outline-primary" onclick="downloadReceipt()">
                                <i class="fas fa-download me-1"></i> Download PDF
                            </button>
                            <a href="process_payment.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-plus me-1"></i> New Payment
                            </a>
                        </div>
                        <?php else: ?>
                        <!-- No Order Selected -->
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-money-bill-wave text-muted fa-4x mb-3"></i>
                                <h4>Select an Order to Process Payment</h4>
                                <p class="text-muted">Choose an order from the list on the left to process payment.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success">RM <?php echo getTotalRevenue($pdo, $_SESSION['user_id']); ?></h3>
                                <p class="mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count($awaiting_payment); ?></h3>
                                <p class="mb-0">Awaiting Payment</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo getPaidOrdersCount($pdo, $_SESSION['user_id']); ?></h3>
                                <p class="mb-0">Paid Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo getTodayPayments($pdo, $_SESSION['user_id']); ?></h3>
                                <p class="mb-0">Today's Payments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cashier.js"></script>
    <script>
        // Payment form validation
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const amountPaid = parseFloat(document.getElementById('amount_paid').value);
            const totalAmount = parseFloat(<?php echo $order_details['total_amount'] ?? 0; ?>);
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (amountPaid <= 0) {
                e.preventDefault();
                alert('Amount paid must be greater than 0!');
                return false;
            }
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method!');
                return false;
            }
            
            // Confirm payment
            if (!confirm('Are you sure you want to process this payment?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-format amount input
        document.getElementById('amount_paid')?.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });

        // Show/hide receipt upload section based on payment method
        const paymentMethodSelect = document.getElementById('payment_method');
        const receiptUploadSection = document.getElementById('receiptUploadSection');
        const receiptFileInput = document.getElementById('receipt_file');

        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', function() {
                const method = this.value;

                // Show upload section for online_payment and qr_code
                if (method === 'online_payment' || method === 'qr_code') {
                    receiptUploadSection.style.display = 'block';
                    receiptFileInput.setAttribute('required', 'required');
                } else {
                    receiptUploadSection.style.display = 'none';
                    receiptFileInput.removeAttribute('required');
                    receiptFileInput.value = ''; // Clear file selection
                }
            });
        }

        // Print receipt function
        function printReceipt() {
            window.print();
        }

        // Download receipt as PDF function
        function downloadReceipt() {
            // Use browser's print dialog with save as PDF option
            alert('Please use your browser\'s Print dialog and select "Save as PDF" as the destination.');
            window.print();
        }
    </script>
</body>
</html>

<?php
// Helper functions for statistics
function getTotalRevenue($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE created_by = ? AND payment_status = 'paid'");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return number_format($result['total'], 2);
    } catch (PDOException $e) {
        return '0.00';
    }
}

function getPaidOrdersCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND payment_status = 'paid'");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function getTodayPayments($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND payment_status = 'paid' AND DATE(updated_at) = CURDATE()");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>