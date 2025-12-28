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

// Handle status updates
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    try {
        // If status is "cancel", delete the order (CASCADE will auto-delete measurements and receipts)
        if ($status === 'cancel') {
            // Delete the order - foreign key CASCADE will automatically delete related records
            $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ? AND created_by = ?");
            $stmt->execute([$order_id, $_SESSION['user_id']]);

            if ($stmt->rowCount() > 0) {
                $success = "Order cancelled and deleted successfully!";
            } else {
                $error = "Unable to cancel order. Order not found or you don't have permission.";
            }
        } else {
            // Normal status update (not cancelled)
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND created_by = ?");
            $stmt->execute([$status, $order_id, $_SESSION['user_id']]);

            if ($stmt->rowCount() > 0) {
                $success = "Order status updated successfully!";
            } else {
                $error = "Unable to update order status. Order not found or you don't have permission.";
            }
        }
    } catch (PDOException $e) {
        error_log("Update order status error: " . $e->getMessage());
        $error = "Unable to update order status: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters - EXCLUDE PAID ORDERS by default
$query = "SELECT o.*, u.full_name as tailor_name 
          FROM orders o 
          LEFT JOIN users u ON o.assigned_tailor = u.user_id 
          WHERE o.created_by = ? AND o.status != 'paid'";
$params = [$_SESSION['user_id']];

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

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - TailorTrack</title>
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
                            <a class="nav-link active" href="manage_orders.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="take_order.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> New Order
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
                                        <option value="">All Statuses (Excluding Paid)</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="cancel" <?php echo $status_filter == 'cancel' ? 'selected' : ''; ?>>Cancel</option>
                                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid (Show All)</option>
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
                            <i class="fas fa-shopping-bag me-2"></i>Active Orders
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
                                                    <form method="POST" class="d-inline" id="statusForm-<?php echo $order['order_id']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <select name="status" class="form-select form-select-sm status-select"
                                                                onchange="updateOrderStatus('<?php echo $order['order_id']; ?>')">
                                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="assigned" <?php echo $order['status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                            <option value="in_progress" <?php echo $order['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="cancel" <?php echo $order['status'] == 'cancel' ? 'selected' : ''; ?>>Cancel</option>
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
                                                        <button class="btn btn-outline-primary view-order"
                                                                data-id="<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($order['status'] == 'pending' && empty($order['assigned_tailor'])): ?>
                                                            <a href="assign_order.php" class="btn btn-outline-warning">
                                                                <i class="fas fa-user-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($order['status'] == 'completed' && $order['payment_status'] == 'pending'): ?>
                                                            <a href="process_payment.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-outline-success">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </a>
                                                            <button class="btn btn-outline-info notify-customer"
                                                                    data-id="<?php echo $order['order_id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($order['customer_name']); ?>"
                                                                    data-phone="<?php echo $order['customer_phone']; ?>"
                                                                    data-email="<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>"
                                                                    data-amount="<?php echo $order['total_amount']; ?>"
                                                                    data-paid="<?php echo $order['amount_paid']; ?>"
                                                                    title="Notify Customer">
                                                                <i class="fas fa-bell"></i>
                                                            </button>
                                                        <?php endif; ?>
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
                                <h3 class="text-danger"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'cancel')); ?></h3>
                                <p class="mb-0">Cancel</p>
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
                                <h3 class="text-dark"><?php echo count($orders); ?></h3>
                                <p class="mb-0">Active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here via cashier.js -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Notification Modal -->
    <div class="modal fade" id="notifyModal" tabindex="-1" aria-labelledby="notifyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="notifyModalLabel">
                        <i class="fas fa-bell me-2"></i>Notify Customer - Order Ready for Collection
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="notifyAlert"></div>

                    <!-- Customer Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <span id="notifyCustomerName"></span></p>
                                    <p><strong>Phone:</strong> <span id="notifyCustomerPhone"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Order ID:</strong> <span id="notifyOrderId"></span></p>
                                    <p><strong>Email:</strong> <span id="notifyCustomerEmail"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Total Amount:</strong><br>
                                        <span class="text-primary h5">RM <span id="notifyTotalAmount"></span></span>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Amount Paid:</strong><br>
                                        <span class="text-success h5">RM <span id="notifyPaidAmount"></span></span>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Balance Due:</strong><br>
                                        <span class="text-danger h5">RM <span id="notifyBalance"></span></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Method -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send Notification</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-success btn-lg w-100" id="sendWhatsAppBtn">
                                        <i class="fab fa-whatsapp me-2"></i>Send WhatsApp Message
                                    </button>
                                    <small class="text-muted d-block mt-2">
                                        Opens WhatsApp with pre-filled message
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-lg w-100" id="sendEmailBtn">
                                        <i class="fas fa-envelope me-2"></i>Send Email
                                    </button>
                                    <small class="text-muted d-block mt-2">
                                        Sends email notification to customer
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cashier.js"></script>
    <script>
        // Update order status with confirmation
        function updateOrderStatus(orderId) {
            const form = document.getElementById('statusForm-' + orderId);
            const statusSelect = form.querySelector('.status-select');
            const newStatus = statusSelect.value;
            
            if (confirm(`Are you sure you want to change the order status to "${newStatus.replace('_', ' ')}"?`)) {
                // Show loading state
                const originalText = statusSelect.innerHTML;
                statusSelect.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                statusSelect.disabled = true;
                
                // Submit the form
                fetch('manage_orders.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.text())
                .then(data => {
                    // Check if update was successful by reloading the page
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating order status. Please try again.');
                    statusSelect.innerHTML = originalText;
                    statusSelect.disabled = false;
                });
            } else {
                // Reset the select to original value
                window.location.reload();
            }
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Re-initialize view order buttons for dynamically created content
        document.addEventListener('DOMContentLoaded', function() {
            // This will ensure the event listeners work for dynamically created buttons
            const viewOrderButtons = document.querySelectorAll('.view-order');
            
            viewOrderButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-id');
                    if (orderId && typeof loadOrderDetails === 'function') {
                        loadOrderDetails(orderId);
                    } else {
                        console.error('loadOrderDetails function not found or orderId missing');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Helper function for paid orders count
function getPaidOrdersCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND status = 'paid'");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>