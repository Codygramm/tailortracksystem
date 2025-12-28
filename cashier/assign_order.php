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

// Handle order assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_tailor'])) {
    $order_id = $_POST['order_id'];
    $tailor_id = $_POST['tailor_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET assigned_tailor = ?, status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND created_by = ?");
        $stmt->execute([$tailor_id, $order_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Order assigned to tailor successfully!";
        } else {
            $error = "Unable to assign order. Order not found or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Assign order error: " . $e->getMessage());
        $error = "Unable to assign order. Please try again.";
    }
}

// Get unassigned orders created by this cashier
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE created_by = ? AND (assigned_tailor IS NULL OR assigned_tailor = '') AND status = 'pending' ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $unassigned_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch unassigned orders error: " . $e->getMessage());
    $error = "Unable to load orders data.";
}

// Get all tailors (simple query without statistics)
try {
    $stmt = $pdo->query("SELECT user_id, username, full_name, email, phone FROM users WHERE role = 'tailor' ORDER BY full_name");
    $tailors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch tailors error: " . $e->getMessage());
    $error = "Unable to load tailors data.";
}

// Get tailor statistics (separate query for statistics display)
$tailor_stats = [];
try {
    foreach ($tailors as $tailor) {
        $tailor_id = $tailor['user_id'];

        // Get total active orders for this tailor
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM orders
            WHERE assigned_tailor = ? AND status != 'cancel' AND status != 'paid'
        ");
        $stmt->execute([$tailor_id]);
        $stats = $stmt->fetch();

        // Get order types breakdown
        $stmt = $pdo->prepare("
            SELECT order_type, COUNT(*) as count
            FROM orders
            WHERE assigned_tailor = ? AND status != 'cancel' AND status != 'paid'
            GROUP BY order_type
        ");
        $stmt->execute([$tailor_id]);
        $order_types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $tailor_stats[$tailor_id] = [
            'name' => $tailor['full_name'],
            'total_orders' => $stats['total_orders'],
            'assigned' => $stats['assigned'],
            'in_progress' => $stats['in_progress'],
            'completed' => $stats['completed'],
            'order_types' => $order_types
        ];
    }
} catch (PDOException $e) {
    error_log("Fetch tailor statistics error: " . $e->getMessage());
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Orders - TailorTrack</title>
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
                            <a class="nav-link active" href="assign_order.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Assign Orders to Tailors</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag me-1"></i> View All Orders
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
                    <!-- Unassigned Orders -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Unassigned Orders
                                </h5>
                                <span class="badge bg-warning"><?php echo count($unassigned_orders); ?> orders</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($unassigned_orders)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                        <h5>All orders are assigned!</h5>
                                        <p class="text-muted">No unassigned orders found.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($unassigned_orders as $order): ?>
                                                    <tr>
                                                        <td><strong><?php echo $order['order_id']; ?></strong></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">
                                                                <?php echo str_replace('_', ' ', $order['order_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary assign-order" 
                                                                    data-orderid="<?php echo $order['order_id']; ?>"
                                                                    data-customer="<?php echo htmlspecialchars($order['customer_name']); ?>"
                                                                    data-type="<?php echo $order['order_type']; ?>">
                                                                <i class="fas fa-user-check me-1"></i> Assign
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Available Tailors -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Available Tailors
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($tailors)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                                        <p class="text-muted">No tailors available.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="tailor-list">
                                        <?php foreach ($tailors as $tailor): ?>
                                            <div class="tailor-item mb-3 p-3 border rounded">
                                                <div class="d-flex align-items-center">
                                                    <div class="tailor-avatar me-3">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($tailor['full_name']); ?></h6>
                                                        <p class="text-muted small mb-1"><?php echo $tailor['email']; ?></p>
                                                        <p class="text-muted small mb-0"><?php echo $tailor['phone']; ?></p>
                                                    </div>
                                                    <div class="tailor-stats text-end">
                                                        <span class="badge bg-tailor">Tailor</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tailor Workload Statistics -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Tailor Workload
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($tailor_stats)): ?>
                                    <p class="text-muted text-center">No statistics available</p>
                                <?php else: ?>
                                    <?php foreach ($tailor_stats as $tailor_id => $stats): ?>
                                        <div class="tailor-stats-card mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($stats['name']); ?></h6>
                                                <span class="badge bg-primary">
                                                    <?php echo $stats['total_orders']; ?> Order<?php echo $stats['total_orders'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </div>

                                            <?php if ($stats['total_orders'] > 0): ?>
                                                <!-- Status Breakdown -->
                                                <div class="mt-2">
                                                    <small class="text-muted d-block mb-1"><strong>Status:</strong></small>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($stats['assigned'] > 0): ?>
                                                            <span class="badge bg-info text-white" style="font-size: 0.75rem;">
                                                                Assigned (<?php echo $stats['assigned']; ?>)
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($stats['in_progress'] > 0): ?>
                                                            <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">
                                                                In Progress (<?php echo $stats['in_progress']; ?>)
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($stats['completed'] > 0): ?>
                                                            <span class="badge bg-success" style="font-size: 0.75rem;">
                                                                Completed (<?php echo $stats['completed']; ?>)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Order Types -->
                                                <?php if (!empty($stats['order_types'])): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted d-block mb-1"><strong>Types:</strong></small>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <?php foreach ($stats['order_types'] as $type => $count): ?>
                                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                    <?php echo ucwords(str_replace('_', ' ', $type)); ?> (<?php echo $count; ?>)
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>No active orders
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Assign Order Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Order to Tailor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="assign_order.php">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="assign_order_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Order Details</label>
                            <div class="border rounded p-3 bg-light">
                                <p class="mb-1"><strong>Order ID:</strong> <span id="assign_order_number"></span></p>
                                <p class="mb-1"><strong>Customer:</strong> <span id="assign_customer_name"></span></p>
                                <p class="mb-0"><strong>Type:</strong> <span id="assign_order_type"></span></p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tailor_id" class="form-label">Select Tailor</label>
                            <select class="form-select" id="tailor_id" name="tailor_id" required>
                                <option value="">Choose a tailor...</option>
                                <?php foreach ($tailors as $tailor): ?>
                                    <option value="<?php echo $tailor['user_id']; ?>">
                                        <?php echo htmlspecialchars($tailor['full_name']); ?> 
                                        (<?php echo $tailor['phone']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="assign_tailor">Assign Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cashier.js"></script>
    <script>
        // Assign order modal
        document.querySelectorAll('.assign-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-orderid');
                const customerName = this.getAttribute('data-customer');
                const orderType = this.getAttribute('data-type');
                
                document.getElementById('assign_order_id').value = orderId;
                document.getElementById('assign_order_number').textContent = orderId;
                document.getElementById('assign_customer_name').textContent = customerName;
                document.getElementById('assign_order_type').textContent = orderType.replace(/_/g, ' ');
                
                const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
                assignModal.show();
            });
        });
    </script>
</body>
</html>