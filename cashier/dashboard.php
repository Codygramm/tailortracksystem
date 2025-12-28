<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

// Get dashboard statistics for cashier
try {
    // Total orders created by this cashier
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE created_by = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_orders = $stmt->fetch()['total_orders'];
    
    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE created_by = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_orders = $stmt->fetch()['pending_orders'];
    
    // Orders awaiting payment
    $stmt = $pdo->prepare("SELECT COUNT(*) as unpaid_orders FROM orders WHERE created_by = ? AND payment_status = 'pending' AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $unpaid_orders = $stmt->fetch()['unpaid_orders'];
    
    // Today's orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_orders FROM orders WHERE created_by = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $today_orders = $stmt->fetch()['today_orders'];
    
    // Recent orders by this cashier
    $stmt = $pdo->prepare("SELECT o.*, t.full_name as tailor_name 
                          FROM orders o 
                          LEFT JOIN users t ON o.assigned_tailor = t.user_id 
                          WHERE o.created_by = ? 
                          ORDER BY o.created_at DESC 
                          LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Cashier dashboard error: " . $e->getMessage());
    $error = "Unable to load dashboard data.";
}

// Get current page for sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - TailorTrack</title>
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
                            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'take_order.php' ? 'active' : ''; ?>" href="take_order.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                Take Order
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'assign_order.php' ? 'active' : ''; ?>" href="assign_order.php">
                                <i class="fas fa-user-check me-2"></i>
                                Assign Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'manage_orders.php' ? 'active' : ''; ?>" href="manage_orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Manage Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'process_payment.php' ? 'active' : ''; ?>" href="process_payment.php">
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
                    <h1 class="h2">Cashier Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">Today</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Week</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Month</button>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Orders</h5>
                                        <h2 class="text-primary"><?php echo $total_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">All your orders</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Pending Orders</h5>
                                        <h2 class="text-warning"><?php echo $pending_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Awaiting processing</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Awaiting Payment</h5>
                                        <h2 class="text-danger"><?php echo $unpaid_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Ready for payment</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Today's Orders</h5>
                                        <h2 class="text-success"><?php echo $today_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Orders today</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Orders
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_orders)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">No orders found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><strong><?php echo $order['order_id']; ?></strong></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">
                                                                <?php echo str_replace('_', ' ', $order['order_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo getStatusBadge($order['status']); ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary view-order" data-id="<?php echo $order['order_id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="manage_orders.php" class="btn btn-primary">View All Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="take_order.php" class="btn btn-primary text-start">
                                        <i class="fas fa-plus-circle me-2"></i>Take New Order
                                    </a>
                                    <a href="assign_order.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-user-check me-2"></i>Assign to Tailor
                                    </a>
                                    <a href="process_payment.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-money-bill-wave me-2"></i>Process Payment
                                    </a>
                                    <a href="manage_orders.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-shopping-bag me-2"></i>Manage Orders
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status Summary -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Orders Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="order-status-summary">
                                    <div class="status-item">
                                        <span class="status-dot pending"></span>
                                        <span>Pending: <?php echo $pending_orders; ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot assigned"></span>
                                        <span>Assigned: <?php echo getOrderCountByStatus($pdo, $_SESSION['user_id'], 'assigned'); ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot in-progress"></span>
                                        <span>In Progress: <?php echo getOrderCountByStatus($pdo, $_SESSION['user_id'], 'in_progress'); ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot completed"></span>
                                        <span>Completed: <?php echo getOrderCountByStatus($pdo, $_SESSION['user_id'], 'completed'); ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot paid"></span>
                                        <span>Paid: <?php echo getOrderCountByStatus($pdo, $_SESSION['user_id'], 'paid'); ?></span>
                                    </div>
                                </div>
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
                    <!-- Order details will be loaded here via AJAX -->
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
        // Event delegation for view order buttons in dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize view order buttons for dashboard
            const viewOrderButtons = document.querySelectorAll('.view-order');
            
            viewOrderButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-id');
                    console.log('View order clicked:', orderId);
                    
                    if (orderId && typeof loadOrderDetails === 'function') {
                        loadOrderDetails(orderId);
                    } else {
                        console.error('loadOrderDetails function not available');
                        // Fallback: show basic modal with order info
                        showBasicOrderInfo(orderId);
                    }
                });
            });

            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('show')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Fallback function if cashier.js doesn't load
        function showBasicOrderInfo(orderId) {
            const orderDetails = document.getElementById('orderDetails');
            orderDetails.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details for ${orderId}...</p>
                    <p class="text-muted small">If this takes too long, please check the console for errors.</p>
                </div>
            `;
            
            const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
            orderModal.show();
            
            // Try to load via AJAX directly
            fetch(`../cashier/get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Use the displayOrderDetails function if available
                        if (typeof displayOrderDetails === 'function') {
                            displayOrderDetails(data.order);
                        } else {
                            // Fallback display
                            orderDetails.innerHTML = `
                                <div class="alert alert-info">
                                    <h6>Order ${data.order.order_id}</h6>
                                    <p>Customer: ${data.order.customer_name}</p>
                                    <p>Type: ${data.order.order_type}</p>
                                    <p>Status: ${data.order.status}</p>
                                </div>
                            `;
                        }
                    } else {
                        orderDetails.innerHTML = `
                            <div class="alert alert-danger">
                                Unable to load order details: ${data.error || 'Unknown error'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    orderDetails.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details. Please try again.
                        </div>
                    `;
                });
        }

        // Event delegation for dynamically created buttons (backup)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-order')) {
                const button = e.target.closest('.view-order');
                const orderId = button.getAttribute('data-id');
                console.log('Event delegation caught view order:', orderId);
                
                if (orderId && typeof loadOrderDetails === 'function') {
                    loadOrderDetails(orderId);
                }
            }
        });
    </script>
</body>
</html>

<?php
// Helper functions
function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return 'secondary';
        case 'assigned': return 'info';
        case 'in_progress': return 'warning';
        case 'completed': return 'success';
        case 'paid': return 'primary';
        default: return 'secondary';
    }
}

function getOrderCountByStatus($pdo, $user_id, $status) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE created_by = ? AND status = ?");
        $stmt->execute([$user_id, $status]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>