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
$order_id = '';
$receipt_data = [];
$show_deposit_form = false;
$order_data = [];

// Handle deposit payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_deposit'])) {
    try {
        $pdo->beginTransaction();

        $order_id = $_POST['order_id'];
        $deposit_amount = floatval($_POST['deposit_amount']);
        $payment_method = $_POST['payment_method'];

        // Verify deposit amount is valid
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception("Order not found");
        }

        if ($deposit_amount < 10 || $deposit_amount > $order['total_amount']) {
            throw new Exception("Invalid deposit amount. Minimum RM10.00");
        }

        // Update order with deposit information and set amount_paid to deposit amount
        $stmt = $pdo->prepare("UPDATE orders SET deposit_amount = ?, amount_paid = ?, deposit_paid = 'yes', deposit_paid_at = CURRENT_TIMESTAMP WHERE order_id = ?");
        $stmt->execute([$deposit_amount, $deposit_amount, $order_id]);

        // Generate receipt number
        $receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Insert receipt record
        $stmt = $pdo->prepare("INSERT INTO receipts (order_id, receipt_number, receipt_type, amount, payment_method, created_by) VALUES (?, ?, 'deposit', ?, ?, ?)");
        $stmt->execute([$order_id, $receipt_number, $deposit_amount, $payment_method, $_SESSION['user_id']]);

        $pdo->commit();

        // Prepare receipt data for display
        $receipt_data = [
            'receipt_number' => $receipt_number,
            'order_id' => $order_id,
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'order_type' => $order['order_type'],
            'repair_type' => $order['repair_type'],
            'total_amount' => $order['total_amount'],
            'deposit_amount' => $deposit_amount,
            'balance' => $order['total_amount'] - $deposit_amount,
            'payment_method' => $payment_method,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $success = "Deposit payment received! Receipt generated successfully.";

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Deposit payment error: " . $e->getMessage());
        $error = "Unable to process deposit payment: " . $e->getMessage();
    }
}

// Handle order creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['pay_deposit'])) {
    try {
        // Customer information
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $customer_email = trim($_POST['customer_email'] ?? '');
        $order_type = $_POST['order_type'];
        $repair_type = $_POST['repair_type'] ?? null;

        // Validate measurements based on order type
        $measurement_error = false;

        if ($order_type == 'set_baju_melayu' || $order_type == 'set_baju_kurung' || $order_type == 'set_baju_kebaya') {
            // Check upper body measurements
            if (empty($_POST['shoulder']) || empty($_POST['chest']) || empty($_POST['upper_waist'])) {
                $error = "Please fill in all required upper body measurements (Shoulder, Chest, Waist)";
                $measurement_error = true;
            }
            // Check lower body measurements
            if (!$measurement_error && (empty($_POST['lower_waist']) || empty($_POST['hip']) || empty($_POST['bottom_length']))) {
                $error = "Please fill in all required lower body measurements (Waist, Hip, Bottom Length)";
                $measurement_error = true;
            }
        } elseif ($order_type == 'baju_kurta') {
            // Check upper body measurements only
            if (empty($_POST['shoulder']) || empty($_POST['chest']) || empty($_POST['upper_waist'])) {
                $error = "Please fill in all required upper body measurements (Shoulder, Chest, Waist)";
                $measurement_error = true;
            }
        } elseif ($order_type == 'repair' && $repair_type) {
            // Check measurements based on repair type
            if ($repair_type == 'upper' || $repair_type == 'both') {
                if (empty($_POST['shoulder']) || empty($_POST['chest']) || empty($_POST['upper_waist'])) {
                    $error = "Please fill in all required upper body measurements (Shoulder, Chest, Waist)";
                    $measurement_error = true;
                }
            }
            if (!$measurement_error && ($repair_type == 'lower' || $repair_type == 'both')) {
                if (empty($_POST['lower_waist']) || empty($_POST['hip']) || empty($_POST['bottom_length'])) {
                    $error = "Please fill in all required lower body measurements (Waist, Hip, Bottom Length)";
                    $measurement_error = true;
                }
            }
        }

        if (!$measurement_error) {
            $pdo->beginTransaction();

            // Generate order ID
            $order_id = 'TT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

            // Calculate total amount based on order type
            $prices = [
                'set_baju_melayu' => 150.00,
                'set_baju_kurung' => 120.00,
                'set_baju_kebaya' => 180.00,
                'baju_kurta' => 80.00,
                'repair' => 25.00
            ];
            $total_amount = $prices[$order_type] ?? 0;

            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (order_id, customer_name, customer_phone, customer_email, order_type, repair_type, total_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $customer_name, $customer_phone, $customer_email, $order_type, $repair_type, $total_amount, $_SESSION['user_id']]);

            // Insert upper body measurements if provided
            if (isset($_POST['shoulder']) && !empty($_POST['shoulder'])) {
                $upper_stmt = $pdo->prepare("INSERT INTO upper_body_measurements (order_id, shoulder, chest, waist, sleeve_length, armhole, wrist, neck, top_length) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $upper_stmt->execute([
                    $order_id,
                    $_POST['shoulder'] ?? null,
                    $_POST['chest'] ?? null,
                    $_POST['upper_waist'] ?? null,
                    $_POST['sleeve_length'] ?? null,
                    $_POST['armhole'] ?? null,
                    $_POST['wrist'] ?? null,
                    $_POST['neck'] ?? null,
                    $_POST['top_length'] ?? null
                ]);
            }

            // Insert lower body measurements if provided
            if (isset($_POST['lower_waist']) && !empty($_POST['lower_waist'])) {
                $lower_stmt = $pdo->prepare("INSERT INTO lower_body_measurements (order_id, waist, hip, bottom_length, inseam, outseam) VALUES (?, ?, ?, ?, ?, ?)");
                $lower_stmt->execute([
                    $order_id,
                    $_POST['lower_waist'] ?? null,
                    $_POST['hip'] ?? null,
                    $_POST['bottom_length'] ?? null,
                    $_POST['inseam'] ?? null,
                    $_POST['outseam'] ?? null
                ]);
            }

            $pdo->commit();

            // Show deposit payment form instead of receipt
            $show_deposit_form = true;
            $order_data = [
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'order_type' => $order_type,
                'repair_type' => $repair_type,
                'total_amount' => $total_amount
            ];

            $success = "Order created successfully! Order ID: " . $order_id . ". Please collect deposit payment.";
        }

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Take order error: " . $e->getMessage());
        $error = "Unable to create order. Please try again.";
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Order - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/cashier.css">
    <style>
        /* Additional styling for repair type cards */
        .repair-type-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .repair-type-card:hover {
            border-color: #6F4D38;
            transform: translateY(-2px);
        }
        
        .repair-type-card.selected {
            border-color: #6F4D38;
            background-color: rgba(111, 77, 56, 0.05);
        }
        
        .repair-type-icon {
            width: 50px;
            height: 50px;
            background: rgba(111, 77, 56, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #6F4D38;
            font-size: 1.25rem;
        }
        
        /* Hidden radio buttons */
        .repair-type-input {
            display: none;
        }
        
        /* Repair type section styling */
        .repair-type-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
    </style>
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
                            <a class="nav-link active" href="take_order.php">
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
                            <a class="nav-link" href="process_payment.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                    <h1 class="h2">Take New Order</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
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

                <!-- Step Wizard -->
                <div class="step-wizard mb-5 no-print">
                    <?php if (!empty($receipt_data)): ?>
                        <!-- Step 3: Receipt Generated -->
                        <div class="step completed">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Order Created</div>
                        </div>
                        <div class="step completed">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Deposit Paid</div>
                        </div>
                        <div class="step active">
                            <div class="step-number">3</div>
                            <div class="step-label">Receipt Generated</div>
                        </div>
                    <?php elseif ($show_deposit_form): ?>
                        <!-- Step 2: Collect Deposit -->
                        <div class="step completed">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Order Created</div>
                        </div>
                        <div class="step active">
                            <div class="step-number">2</div>
                            <div class="step-label">Collect Deposit</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-label">Generate Receipt</div>
                        </div>
                    <?php else: ?>
                        <!-- Step 1: Create Order -->
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-label">Create Order</div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-label">Collect Deposit</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-label">Generate Receipt</div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($show_deposit_form): ?>
                <!-- Deposit Payment Form -->
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-money-bill-wave me-2"></i>Collect Deposit Payment
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Order created successfully! Please collect deposit payment from customer.
                                </div>

                                <div class="order-summary-box mb-4">
                                    <h6 class="text-secondary mb-3">Order Summary</h6>
                                    <div class="receipt-item">
                                        <span>Order ID:</span>
                                        <span><strong><?php echo $order_data['order_id']; ?></strong></span>
                                    </div>
                                    <div class="receipt-item">
                                        <span>Customer:</span>
                                        <span><?php echo htmlspecialchars($order_data['customer_name']); ?></span>
                                    </div>
                                    <div class="receipt-item">
                                        <span>Phone:</span>
                                        <span><?php echo $order_data['customer_phone']; ?></span>
                                    </div>
                                    <div class="receipt-item">
                                        <span>Order Type:</span>
                                        <span><?php echo str_replace('_', ' ', ucwords($order_data['order_type'])); ?></span>
                                    </div>
                                    <div class="receipt-item">
                                        <span>Total Amount:</span>
                                        <span><strong>RM <?php echo number_format($order_data['total_amount'], 2); ?></strong></span>
                                    </div>
                                </div>

                                <form method="POST" action="take_order.php" id="depositForm">
                                    <input type="hidden" name="order_id" value="<?php echo $order_data['order_id']; ?>">
                                    <input type="hidden" name="pay_deposit" value="1">

                                    <div class="mb-3">
                                        <label for="deposit_amount" class="form-label">Deposit Amount (RM) *</label>
                                        <input type="number" class="form-control form-control-lg"
                                               id="deposit_amount" name="deposit_amount"
                                               step="0.01" min="10" max="<?php echo $order_data['total_amount']; ?>"
                                               placeholder="Minimum RM 10.00" required>
                                        <small class="text-muted">Minimum: RM 10.00 | Maximum: RM <?php echo number_format($order_data['total_amount'], 2); ?></small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select form-select-lg" id="payment_method" name="payment_method" required>
                                            <option value="">Select payment method</option>
                                            <option value="cash" selected>Cash</option>
                                            <option value="card">Card</option>
                                            <option value="online_transfer">Online Transfer</option>
                                        </select>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-check-circle me-2"></i> Confirm Payment & Generate Receipt
                                        </button>
                                        <a href="take_order.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php elseif (empty($receipt_data)): ?>
                <!-- Order Form -->
                <form method="POST" action="take_order.php" id="orderForm">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Customer Information -->
                            <div class="form-section">
                                <h5><i class="fas fa-user me-2"></i>Customer Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_name" class="form-label">Customer Name *</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email">
                                    </div>
                                </div>
                            </div>

                            <!-- Order Type Selection -->
                            <div class="form-section">
                                <h5><i class="fas fa-tshirt me-2"></i>Order Type</h5>
                                <div class="order-type-cards">
                                    <div class="order-type-card" onclick="selectOrderType('set_baju_melayu')">
                                        <div class="order-type-icon">
                                            <i class="fas fa-vest"></i>
                                        </div>
                                        <h6>Set Baju Melayu</h6>
                                        <p class="text-muted mb-1">RM 150.00</p>
                                        <small>Upper + Lower Body</small>
                                    </div>
                                    <div class="order-type-card" onclick="selectOrderType('set_baju_kurung')">
                                        <div class="order-type-icon">
                                            <i class="fas fa-female"></i>
                                        </div>
                                        <h6>Set Baju Kurung</h6>
                                        <p class="text-muted mb-1">RM 120.00</p>
                                        <small>Upper + Lower Body</small>
                                    </div>
                                    <div class="order-type-card" onclick="selectOrderType('set_baju_kebaya')">
                                        <div class="order-type-icon">
                                            <i class="fas fa-gem"></i>
                                        </div>
                                        <h6>Set Baju Kebaya</h6>
                                        <p class="text-muted mb-1">RM 180.00</p>
                                        <small>Upper + Lower Body</small>
                                    </div>
                                    <div class="order-type-card" onclick="selectOrderType('baju_kurta')">
                                        <div class="order-type-icon">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <h6>Baju Kurta</h6>
                                        <p class="text-muted mb-1">RM 80.00</p>
                                        <small>Upper Body Only</small>
                                    </div>
                                    <div class="order-type-card" onclick="selectOrderType('repair')">
                                        <div class="order-type-icon">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <h6>Repair Service</h6>
                                        <p class="text-muted mb-1">RM 25.00</p>
                                        <small>Clothing Repair</small>
                                    </div>
                                </div>
                                <input type="hidden" name="order_type" id="order_type" required>
                            </div>

                            <!-- Repair Type (only for repair orders) -->
                            <div class="form-section" id="repair-type-section" style="display: none;">
                                <h5><i class="fas fa-tools me-2"></i>Repair Type</h5>
                                <div class="repair-type-cards">
                                    <div class="repair-type-card" onclick="selectRepairType('upper')">
                                        <div class="repair-type-icon">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <h6>Upper Body Only</h6>
                                        <p class="text-muted small">Shirt, Blouse, etc.</p>
                                        <input type="radio" class="repair-type-input" name="repair_type" id="upper_repair" value="upper">
                                    </div>
                                    <div class="repair-type-card" onclick="selectRepairType('lower')">
                                        <div class="repair-type-icon">
                                            <i class="fas fa-vest"></i>
                                        </div>
                                        <h6>Lower Body Only</h6>
                                        <p class="text-muted small">Pants, Skirt, etc.</p>
                                        <input type="radio" class="repair-type-input" name="repair_type" id="lower_repair" value="lower">
                                    </div>
                                    <div class="repair-type-card" onclick="selectRepairType('both')">
                                        <div class="repair-type-icon">
                                            <i class="fas fa-tshirt"></i><i class="fas fa-vest ms-1"></i>
                                        </div>
                                        <h6>Both Upper & Lower</h6>
                                        <p class="text-muted small">Complete set</p>
                                        <input type="radio" class="repair-type-input" name="repair_type" id="both_repair" value="both">
                                    </div>
                                </div>
                            </div>

                            <!-- Upper Body Measurements -->
                            <div class="form-section" id="upper-body-section" style="display: none;">
                                <h5><i class="fas fa-ruler-vertical me-2"></i>Upper Body Measurements (inches) <span class="text-danger">* Required</span></h5>
                                <div class="measurement-grid">
                                    <div class="measurement-input">
                                        <label for="shoulder">Shoulder *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="shoulder" name="shoulder" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="chest">Chest *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="chest" name="chest" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="upper_waist">Waist *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="upper_waist" name="upper_waist" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="sleeve_length">Sleeve Length</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="sleeve_length" name="sleeve_length" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="armhole">Armhole</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="armhole" name="armhole" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="wrist">Wrist</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="wrist" name="wrist" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="neck">Neck</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="neck" name="neck" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="top_length">Top Length</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="top_length" name="top_length" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lower Body Measurements -->
                            <div class="form-section" id="lower-body-section" style="display: none;">
                                <h5><i class="fas fa-ruler-vertical me-2"></i>Lower Body Measurements (inches) <span class="text-danger">* Required</span></h5>
                                <div class="measurement-grid">
                                    <div class="measurement-input">
                                        <label for="lower_waist">Waist *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="lower_waist" name="lower_waist" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="hip">Hip *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="hip" name="hip" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="bottom_length">Bottom Length *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-measurement" id="bottom_length" name="bottom_length" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="inseam">Inseam</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="inseam" name="inseam" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                    <div class="measurement-input">
                                        <label for="outseam">Outseam</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="outseam" name="outseam" step="0.1" min="0">
                                            <span class="input-group-text">in</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Order Summary -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-receipt me-2"></i>Order Summary
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="orderSummary">
                                        <p class="text-muted text-center">Select order type to see summary</p>
                                    </div>
                                    <div class="d-grid gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary" id="submitOrder">
                                            <i class="fas fa-check me-1"></i> Create Order
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                            <i class="fas fa-redo me-1"></i> Reset Form
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Measurement Guide -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Measurement Guide
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="small">
                                        <p><strong>Shoulder:</strong> Across the back from shoulder edge to shoulder edge</p>
                                        <p><strong>Chest:</strong> Around the fullest part of the chest</p>
                                        <p><strong>Waist:</strong> Around the natural waistline</p>
                                        <p><strong>Sleeve Length:</strong> From shoulder to wrist</p>
                                        <p><strong>Top Length:</strong> From shoulder to desired length</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php else: ?>
                <!-- Receipt Display -->
                <div class="row justify-content-center">
                    <div class="col-lg-7">
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
                                        <span class="badge-deposit">DEPOSIT RECEIPT</span>
                                    </div>
                                </div>

                                <!-- Receipt Info Bar -->
                                <div class="receipt-info-bar">
                                    <div class="info-item">
                                        <span class="info-label">Receipt No:</span>
                                        <span class="info-value"><strong><?php echo $receipt_data['receipt_number']; ?></strong></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date:</span>
                                        <span class="info-value"><?php echo date('d M Y, g:i A', strtotime($receipt_data['created_at'])); ?></span>
                                    </div>
                                </div>

                                <!-- Customer Details Section -->
                                <div class="receipt-section">
                                    <h6 class="section-title">CUSTOMER DETAILS</h6>
                                    <table class="details-table">
                                        <tr>
                                            <td class="label-col">Order ID:</td>
                                            <td class="value-col"><strong><?php echo $receipt_data['order_id']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">Customer Name:</td>
                                            <td class="value-col"><?php echo htmlspecialchars($receipt_data['customer_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">Phone Number:</td>
                                            <td class="value-col"><?php echo htmlspecialchars($receipt_data['customer_phone']); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Order Details Section -->
                                <div class="receipt-section">
                                    <h6 class="section-title">ORDER DETAILS</h6>
                                    <table class="details-table">
                                        <tr>
                                            <td class="label-col">Order Type:</td>
                                            <td class="value-col"><?php echo str_replace('_', ' ', ucwords($receipt_data['order_type'])); ?></td>
                                        </tr>
                                        <?php if ($receipt_data['repair_type']): ?>
                                        <tr>
                                            <td class="label-col">Repair Type:</td>
                                            <td class="value-col"><?php echo ucfirst($receipt_data['repair_type']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="label-col">Payment Method:</td>
                                            <td class="value-col"><?php echo ucwords(str_replace('_', ' ', $receipt_data['payment_method'])); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Payment Summary Section -->
                                <div class="receipt-section payment-section">
                                    <h6 class="section-title">PAYMENT SUMMARY</h6>
                                    <table class="payment-table">
                                        <tr>
                                            <td class="label-col">Total Order Amount:</td>
                                            <td class="amount-col">RM <?php echo number_format($receipt_data['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr class="deposit-row">
                                            <td class="label-col">Deposit Paid:</td>
                                            <td class="amount-col">RM <?php echo number_format($receipt_data['deposit_amount'], 2); ?></td>
                                        </tr>
                                        <tr class="balance-row">
                                            <td class="label-col"><strong>Balance Due:</strong></td>
                                            <td class="amount-col"><strong>RM <?php echo number_format($receipt_data['balance'], 2); ?></strong></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Footer -->
                                <div class="receipt-footer">
                                    <div class="footer-divider"></div>
                                    <div class="footer-notes">
                                        <p><i class="fas fa-info-circle"></i> <strong>Important:</strong> Please keep this receipt for order tracking and final payment.</p>
                                        <p><i class="fas fa-clock"></i> Balance payment due upon order completion.</p>
                                    </div>
                                    <div class="footer-thank-you">
                                        <p>Thank you for choosing our services!</p>
                                        <p class="signature-line">___________________________</p>
                                        <p class="signature-label">Cashier Signature</p>
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
                            <a href="take_order.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-plus me-1"></i> New Order
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cashier.js"></script>
    <script>
        // Order type selection
        function selectOrderType(type) {
            // Remove selected class from all order type cards
            document.querySelectorAll('.order-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Store selected order type
            document.getElementById('order_type').value = type;
            
            // Update order summary
            updateOrderSummary(type);
            
            // Show/hide measurement sections based on order type
            updateMeasurementSections(type);
            
            // Clear any selected repair type when changing order type
            if (type !== 'repair') {
                clearRepairTypeSelection();
            }
        }

        // Repair type selection
        function selectRepairType(type) {
            // Remove selected class from all repair type cards
            document.querySelectorAll('.repair-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Update the corresponding radio button
            document.getElementById(type + '_repair').checked = true;
            
            // Show/hide measurement sections based on repair type
            updateRepairMeasurementSections(type);
        }

        // Clear repair type selection
        function clearRepairTypeSelection() {
            document.querySelectorAll('.repair-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.repair-type-input').forEach(input => {
                input.checked = false;
            });
        }

        // Update order summary
        function updateOrderSummary(orderType) {
            const prices = {
                'set_baju_melayu': 150.00,
                'set_baju_kurung': 120.00,
                'set_baju_kebaya': 180.00,
                'baju_kurta': 80.00,
                'repair': 25.00
            };
            
            const orderSummary = document.getElementById('orderSummary');
            const totalAmount = prices[orderType] || 0;
            
            orderSummary.innerHTML = `
                <div class="receipt-item">
                    <span>Order Type:</span>
                    <span>${orderType.replace(/_/g, ' ')}</span>
                </div>
                <div class="receipt-item">
                    <span>Amount:</span>
                    <span>RM ${totalAmount.toFixed(2)}</span>
                </div>
                <div class="receipt-item receipt-total">
                    <span><strong>Total:</strong></span>
                    <span><strong>RM ${totalAmount.toFixed(2)}</strong></span>
                </div>
            `;
        }

        // Update measurement sections based on order type
        function updateMeasurementSections(orderType) {
            const upperBodySection = document.getElementById('upper-body-section');
            const lowerBodySection = document.getElementById('lower-body-section');
            const repairTypeSection = document.getElementById('repair-type-section');
            
            // Hide all sections first
            if (upperBodySection) upperBodySection.style.display = 'none';
            if (lowerBodySection) lowerBodySection.style.display = 'none';
            if (repairTypeSection) repairTypeSection.style.display = 'none';
            
            // Show sections based on order type
            switch(orderType) {
                case 'set_baju_melayu':
                case 'set_baju_kurung':
                case 'set_baju_kebaya':
                    if (upperBodySection) upperBodySection.style.display = 'block';
                    if (lowerBodySection) lowerBodySection.style.display = 'block';
                    break;
                case 'baju_kurta':
                    if (upperBodySection) upperBodySection.style.display = 'block';
                    break;
                case 'repair':
                    if (repairTypeSection) repairTypeSection.style.display = 'block';
                    break;
            }
        }

        // Update measurement sections based on repair type
        function updateRepairMeasurementSections(repairType) {
            const upperBodySection = document.getElementById('upper-body-section');
            const lowerBodySection = document.getElementById('lower-body-section');
            
            // Hide all sections first
            if (upperBodySection) upperBodySection.style.display = 'none';
            if (lowerBodySection) lowerBodySection.style.display = 'none';
            
            // Show sections based on repair type
            switch(repairType) {
                case 'upper':
                    if (upperBodySection) upperBodySection.style.display = 'block';
                    break;
                case 'lower':
                    if (lowerBodySection) lowerBodySection.style.display = 'block';
                    break;
                case 'both':
                    if (upperBodySection) upperBodySection.style.display = 'block';
                    if (lowerBodySection) lowerBodySection.style.display = 'block';
                    break;
            }
        }

        // Form validation
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const orderType = document.getElementById('order_type').value;
            const customerName = document.getElementById('customer_name').value;
            const customerPhone = document.getElementById('customer_phone').value;

            if (!orderType) {
                e.preventDefault();
                alert('Please select an order type!');
                return false;
            }

            if (!customerName || !customerPhone) {
                e.preventDefault();
                alert('Please fill in customer name and phone number!');
                return false;
            }

            // For repair orders, check if repair type is selected
            if (orderType === 'repair') {
                const repairTypeSelected = document.querySelector('input[name="repair_type"]:checked');
                if (!repairTypeSelected) {
                    e.preventDefault();
                    alert('Please select a repair type!');
                    return false;
                }

                const repairType = repairTypeSelected.value;

                // Validate measurements based on repair type
                if (repairType === 'upper' || repairType === 'both') {
                    if (!validateUpperBodyMeasurements()) {
                        e.preventDefault();
                        return false;
                    }
                }
                if (repairType === 'lower' || repairType === 'both') {
                    if (!validateLowerBodyMeasurements()) {
                        e.preventDefault();
                        return false;
                    }
                }
            } else if (orderType === 'set_baju_melayu' || orderType === 'set_baju_kurung' || orderType === 'set_baju_kebaya') {
                // Validate both upper and lower body measurements
                if (!validateUpperBodyMeasurements() || !validateLowerBodyMeasurements()) {
                    e.preventDefault();
                    return false;
                }
            } else if (orderType === 'baju_kurta') {
                // Validate upper body measurements only
                if (!validateUpperBodyMeasurements()) {
                    e.preventDefault();
                    return false;
                }
            }

            return true;
        });

        // Validate upper body measurements
        function validateUpperBodyMeasurements() {
            const shoulder = document.getElementById('shoulder').value;
            const chest = document.getElementById('chest').value;
            const upperWaist = document.getElementById('upper_waist').value;

            if (!shoulder || !chest || !upperWaist) {
                alert('Please fill in all required upper body measurements:\n- Shoulder\n- Chest\n- Waist');

                // Highlight missing fields
                if (!shoulder) document.getElementById('shoulder').classList.add('is-invalid');
                if (!chest) document.getElementById('chest').classList.add('is-invalid');
                if (!upperWaist) document.getElementById('upper_waist').classList.add('is-invalid');

                return false;
            }
            return true;
        }

        // Validate lower body measurements
        function validateLowerBodyMeasurements() {
            const lowerWaist = document.getElementById('lower_waist').value;
            const hip = document.getElementById('hip').value;
            const bottomLength = document.getElementById('bottom_length').value;

            if (!lowerWaist || !hip || !bottomLength) {
                alert('Please fill in all required lower body measurements:\n- Waist\n- Hip\n- Bottom Length');

                // Highlight missing fields
                if (!lowerWaist) document.getElementById('lower_waist').classList.add('is-invalid');
                if (!hip) document.getElementById('hip').classList.add('is-invalid');
                if (!bottomLength) document.getElementById('bottom_length').classList.add('is-invalid');

                return false;
            }
            return true;
        }

        // Remove invalid class on input
        document.querySelectorAll('.required-measurement').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Reset form function
        function resetForm() {
            // Clear all selections
            document.querySelectorAll('.order-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.repair-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Reset form values
            document.getElementById('order_type').value = '';
            document.querySelectorAll('.repair-type-input').forEach(input => {
                input.checked = false;
            });
            
            // Hide all measurement sections
            document.getElementById('upper-body-section').style.display = 'none';
            document.getElementById('lower-body-section').style.display = 'none';
            document.getElementById('repair-type-section').style.display = 'none';
            
            // Reset order summary
            document.getElementById('orderSummary').innerHTML = `
                <p class="text-muted text-center">Select order type to see summary</p>
            `;
        }

        // Print receipt
        function printReceipt() {
            window.print();
        }

        // Download receipt as PDF
        function downloadReceipt() {
            showToast('Receipt download would be implemented here', 'info');
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Remove from DOM after hide
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
    </script>
</body>
</html>