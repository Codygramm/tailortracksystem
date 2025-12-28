<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is tailor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tailor') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $tailor_notes = $_POST['tailor_notes'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, tailor_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND assigned_tailor = ?");
        $stmt->execute([$status, $tailor_notes, $order_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            $success = "Order status updated successfully!";
        } else {
            $error = "Unable to update order status. Order not found or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Update order status error: " . $e->getMessage());
        $error = "Unable to update order status.";
    }
}

// Get dashboard statistics for tailor
try {
    // Total assigned orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE assigned_tailor = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_orders = $stmt->fetch()['total_orders'];
    
    // Orders in progress
    $stmt = $pdo->prepare("SELECT COUNT(*) as in_progress_orders FROM orders WHERE assigned_tailor = ? AND status = 'in_progress'");
    $stmt->execute([$_SESSION['user_id']]);
    $in_progress_orders = $stmt->fetch()['in_progress_orders'];
    
    // Completed orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM orders WHERE assigned_tailor = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $completed_orders = $stmt->fetch()['completed_orders'];
    
    // Today's assigned orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_orders FROM orders WHERE assigned_tailor = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $today_orders = $stmt->fetch()['today_orders'];
    
    // Recent assigned orders
    $stmt = $pdo->prepare("SELECT o.*, c.full_name as cashier_name 
                          FROM orders o 
                          JOIN users c ON o.created_by = c.user_id 
                          WHERE o.assigned_tailor = ? 
                          ORDER BY o.created_at DESC 
                          LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Tailor dashboard error: " . $e->getMessage());
    $error = "Unable to load dashboard data.";
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Dashboard - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/tailor.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark tailor-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../Asset/logo_icon.png" alt="TailorTrack" style="width: 30px; height: 30px; object-fit: contain;" class="me-2">
                <span>TailorTrack</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tailorNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="tailorNavbar">
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
                            <a class="nav-link <?php echo $current_page == 'my_orders.php' ? 'active' : ''; ?>" href="my_orders.php">
                                <i class="fas fa-list-alt me-2"></i>
                                My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'completed_orders.php' ? 'active' : ''; ?>" href="completed_orders.php">
                                <i class="fas fa-check-circle me-2"></i>
                                Completed Orders
                            </a>
                        </li>
                    </ul>
                    
                    <div class="sidebar-footer mt-4">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-cut"></i>
                            </div>
                            <div class="user-details">
                                <h6><?php echo $_SESSION['full_name']; ?></h6>
                                <span class="badge bg-tailor">Tailor</span>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tailor Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">Today</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Week</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Month</button>
                        </div>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Assigned</h5>
                                        <h2 class="text-primary"><?php echo $total_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">All assigned orders</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">In Progress</h5>
                                        <h2 class="text-warning"><?php echo $in_progress_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Currently working on</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Completed</h5>
                                        <h2 class="text-success"><?php echo $completed_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Finished orders</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Today's Assignments</h5>
                                        <h2 class="text-info"><?php echo $today_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Assigned today</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Assigned Orders -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Assigned Orders
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
                                                <th>Assigned By</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_orders)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">No orders assigned yet</td>
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
                                                        <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary view-order" data-id="<?php echo $order['order_id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if ($order['status'] != 'completed'): ?>
                                                                <button class="btn btn-sm btn-outline-success update-status" data-id="<?php echo $order['order_id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="my_orders.php" class="btn btn-primary">View All Orders</a>
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
                                    <a href="my_orders.php" class="btn btn-primary text-start">
                                        <i class="fas fa-list-alt me-2"></i>View My Orders
                                    </a>
                                    <a href="completed_orders.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-check-circle me-2"></i>Completed Orders
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
                                        <span class="status-dot assigned"></span>
                                        <span>Assigned: <?php echo getOrderCountByStatus($pdo, $_SESSION['user_id'], 'assigned'); ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot in-progress"></span>
                                        <span>In Progress: <?php echo $in_progress_orders; ?></span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-dot completed"></span>
                                        <span>Completed: <?php echo $completed_orders; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Stats -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Performance
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $completion_rate = $total_orders > 0 ? ($completed_orders / $total_orders) * 100 : 0;
                                $performance_class = $completion_rate >= 80 ? 'text-success' : ($completion_rate >= 60 ? 'text-warning' : 'text-danger');
                                ?>
                                <div class="text-center">
                                    <h3 class="<?php echo $performance_class; ?>"><?php echo number_format($completion_rate, 1); ?>%</h3>
                                    <p class="text-muted mb-0">Completion Rate</p>
                                    <small class="text-muted"><?php echo $completed_orders; ?> of <?php echo $total_orders; ?> orders</small>
                                </div>
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
                    <!-- Order details will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="dashboard.php" id="updateStatusForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="statusModalBody">
                        <!-- Status form fields will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/tailor.js"></script>
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE assigned_tailor = ? AND status = ?");
        $stmt->execute([$user_id, $status]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>