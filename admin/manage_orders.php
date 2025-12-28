<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Handle status updates
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $order_id]);
        
        $success = "Order status updated successfully!";
    } catch (PDOException $e) {
        error_log("Update order status error: " . $e->getMessage());
        $error = "Unable to update order status.";
    }
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $order_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $success = "Order deleted successfully!";
    } catch (PDOException $e) {
        error_log("Delete order error: " . $e->getMessage());
        $error = "Unable to delete order.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT o.*, u.full_name as cashier_name, t.full_name as tailor_name 
          FROM orders o 
          LEFT JOIN users u ON o.created_by = u.user_id 
          LEFT JOIN users t ON o.assigned_tailor = t.user_id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $query .= " AND o.order_type = ?";
    $params[] = $type_filter;
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch orders error: " . $e->getMessage());
    $error = "Unable to load orders data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../Asset/logo_icon.png" alt="TailorTrack" style="width: 30px; height: 30px; object-fit: contain;" class="me-2">
                <span>TailorTrack</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
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
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="reports.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-1"></i> Generate Reports
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filter Orders
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="manage_orders.php">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Order Type</label>
                                    <select class="form-select" id="type" name="type">
                                        <option value="">All Types</option>
                                        <option value="set_baju_melayu" <?php echo $type_filter == 'set_baju_melayu' ? 'selected' : ''; ?>>Set Baju Melayu</option>
                                        <option value="set_baju_kurung" <?php echo $type_filter == 'set_baju_kurung' ? 'selected' : ''; ?>>Set Baju Kurung</option>
                                        <option value="set_baju_kebaya" <?php echo $type_filter == 'set_baju_kebaya' ? 'selected' : ''; ?>>Set Baju Kebaya</option>
                                        <option value="baju_kurta" <?php echo $type_filter == 'baju_kurta' ? 'selected' : ''; ?>>Baju Kurta</option>
                                        <option value="repair" <?php echo $type_filter == 'repair' ? 'selected' : ''; ?>>Repair</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <a href="manage_orders.php" class="btn btn-outline-secondary">Clear Filters</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shopping-bag me-2"></i>All Orders
                        </h5>
                        <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Tailor</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_id']; ?></strong></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo $order['customer_phone']; ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo str_replace('_', ' ', $order['order_type']); ?>
                                                    </span>
                                                    <?php if ($order['order_type'] == 'repair' && $order['repair_type']): ?>
                                                        <br>
                                                        <small class="text-muted">(<?php echo $order['repair_type']; ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <select name="status" class="form-select form-select-sm status-select" onchange="this.form.submit()">
                                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="assigned" <?php echo $order['status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                            <option value="in_progress" <?php echo $order['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                </td>
                                                <td>
                                                    <?php if ($order['tailor_name']): ?>
                                                        <?php echo htmlspecialchars($order['tailor_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary view-order" data-id="<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="#" class="btn btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger delete-order" data-id="<?php echo $order['order_id']; ?>" data-customer="<?php echo htmlspecialchars($order['customer_name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="row mt-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-secondary"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'pending')); ?></h3>
                                <p class="mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'assigned')); ?></h3>
                                <p class="mb-0">Assigned</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'in_progress')); ?></h3>
                                <p class="mb-0">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'completed')); ?></h3>
                                <p class="mb-0">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'paid')); ?></h3>
                                <p class="mb-0">Paid</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-dark"><?php echo count($orders); ?></h3>
                                <p class="mb-0">Total</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirm Order Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete order for <strong id="deleteCustomerName"></strong>?</p>
                    <p class="text-danger"><small>This will permanently delete the order and all associated measurements.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmOrderDelete" class="btn btn-danger">Delete Order</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
    <script>
        // Order view modal
        document.querySelectorAll('.view-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                // In real implementation, load order details via AJAX
                document.getElementById('orderDetails').innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                `;
                
                const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
                orderModal.show();
                
                // Simulate AJAX call
                /*setTimeout(() => {
                    document.getElementById('orderDetails').innerHTML = `
                        <div class="order-details">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Order Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Order ID:</strong></td>
                                            <td>${orderId}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer Name:</strong></td>
                                            <td>Ali bin Ahmad</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>012-345 6789</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Order Type:</strong></td>
                                            <td>Set Baju Melayu</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge bg-warning">In Progress</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Amount:</strong></td>
                                            <td>RM 150.00</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Measurement Details</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Chest:</strong></td>
                                            <td>42 inches</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Waist:</strong></td>
                                            <td>38 inches</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Shoulder:</strong></td>
                                            <td>18 inches</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sleeve:</strong></td>
                                            <td>24 inches</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                }, 1000);
            });
        });*/

        // Delete confirmation
        document.querySelectorAll('.delete-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-customer');
                
                document.getElementById('deleteCustomerName').textContent = customerName;
                document.getElementById('confirmOrderDelete').href = `manage_orders.php?delete=${orderId}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>