<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get dashboard statistics
try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $total_orders = $stmt->fetch()['total_orders'];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch()['pending_orders'];
    
    // Completed orders this month
    $stmt = $pdo->query("SELECT COUNT(*) as completed_orders FROM orders WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $completed_orders = $stmt->fetch()['completed_orders'];
    
    // Recent orders
    $stmt = $pdo->query("SELECT o.*, u.full_name as cashier_name 
                         FROM orders o 
                         JOIN users u ON o.created_by = u.user_id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5");
    $recent_orders = $stmt->fetchAll();
    
    // Staff counts
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $staff_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Unable to load dashboard data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TailorTrack</title>
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
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register_staff.php">
                                <i class="fas fa-user-plus me-2"></i>
                                Register Staff
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_staff.php">
                                <i class="fas fa-users me-2"></i>
                                Manage Staff
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Manage Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                Reports
                            </a>
                        </li>
                    </ul>
                    
                    <div class="sidebar-footer mt-4">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="user-details">
                                <h6><?php echo $_SESSION['full_name']; ?></h6>
                                <span class="badge bg-admin">Administrator</span>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
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
                                <p class="card-text"><small class="text-muted">All time orders</small></p>
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
                                        <h5 class="card-title">Staff Members</h5>
                                        <h2 class="text-success"><?php echo $total_users; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Total team members</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Completed (Month)</h5>
                                        <h2 class="text-info"><?php echo $completed_orders; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">This month's completed</small></p>
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
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_orders)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">No orders found</td>
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

                    <!-- Staff Overview -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Staff Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="staff-stats">
                                    <div class="staff-stat-item">
                                        <div class="stat-icon admin">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h6>Administrators</h6>
                                            <p class="mb-0"><?php echo $staff_counts['admin'] ?? 0; ?> members</p>
                                        </div>
                                    </div>
                                    <div class="staff-stat-item">
                                        <div class="stat-icon cashier">
                                            <i class="fas fa-cash-register"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h6>Cashiers</h6>
                                            <p class="mb-0"><?php echo $staff_counts['cashier'] ?? 0; ?> members</p>
                                        </div>
                                    </div>
                                    <div class="staff-stat-item">
                                        <div class="stat-icon tailor">
                                            <i class="fas fa-cut"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h6>Tailors</h6>
                                            <p class="mb-0"><?php echo $staff_counts['tailor'] ?? 0; ?> members</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="manage_staff.php" class="btn btn-outline-primary me-2">Manage Staff</a>
                                    <a href="register_staff.php" class="btn btn-primary">Add Staff</a>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="register_staff.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-user-plus me-2"></i>Register New Staff
                                    </a>
                                    <a href="manage_orders.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-shopping-bag me-2"></i>Manage Orders
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                    </a>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>

<?php
// Helper function for status badges
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
?>