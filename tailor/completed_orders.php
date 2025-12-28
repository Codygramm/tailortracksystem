<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is tailor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tailor') {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? '';

// Build query with filters for completed orders
$query = "SELECT o.*, c.full_name as cashier_name 
          FROM orders o 
          JOIN users c ON o.created_by = c.user_id 
          WHERE o.assigned_tailor = ? AND o.status = 'completed'";
$params = [$_SESSION['user_id']];

if ($date_from) {
    $query .= " AND DATE(o.updated_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.updated_at) <= ?";
    $params[] = $date_to;
}

if ($type_filter) {
    $query .= " AND o.order_type = ?";
    $params[] = $type_filter;
}

$query .= " ORDER BY o.updated_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $completed_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch completed orders error: " . $e->getMessage());
    $error = "Unable to load completed orders data.";
}

// Get statistics for completed orders
try {
    // Total completed orders
    $total_completed = count($completed_orders);
    
    // This month's completions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE assigned_tailor = ? AND status = 'completed' AND MONTH(updated_at) = MONTH(CURRENT_DATE())");
    $stmt->execute([$_SESSION['user_id']]);
    $month_completed = $stmt->fetch()['count'];
    
    // Total revenue from completed orders
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE assigned_tailor = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $total_revenue = $stmt->fetch()['total'];
    
    // Average completion time (in days)
    $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM orders WHERE assigned_tailor = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $avg_completion_time = $stmt->fetch()['avg_days'];
    
} catch (PDOException $e) {
    error_log("Fetch statistics error: " . $e->getMessage());
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Orders - TailorTrack</title>
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_orders.php">
                                <i class="fas fa-list-alt me-2"></i>
                                My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="completed_orders.php">
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
                    <h1 class="h2">Completed Orders</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="my_orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to My Orders
                        </a>
                    </div>
                </div>

                <!-- Performance Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Completed</h5>
                                        <h2 class="text-success"><?php echo $total_completed; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">All time completed orders</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">This Month</h5>
                                        <h2 class="text-primary"><?php echo $month_completed; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Completed this month</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Revenue</h5>
                                        <h2 class="text-info">RM <?php echo number_format($total_revenue, 2); ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">From completed orders</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Avg. Completion</h5>
                                        <h2 class="text-warning"><?php echo number_format($avg_completion_time, 1); ?> days</h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <p class="card-text"><small class="text-muted">Average time to complete</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filter Completed Orders
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="completed_orders.php">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Completion Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Completion Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
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
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search me-1"></i> Apply Filters
                                        </button>
                                        <a href="completed_orders.php" class="btn btn-outline-secondary">Clear Filters</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Completed Orders Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-check-circle me-2"></i>Completed Orders History
                        </h5>
                        <span class="badge bg-success"><?php echo count($completed_orders); ?> orders</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completed_orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-check text-muted fa-4x mb-3"></i>
                                <h4>No Completed Orders</h4>
                                <p class="text-muted">You haven't completed any orders yet.</p>
                                <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
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
                                            <th>Assigned By</th>
                                            <th>Completed Date</th>
                                            <th>Completion Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completed_orders as $order): ?>
                                            <?php
                                            $completion_time = date_diff(
                                                date_create($order['created_at']),
                                                date_create($order['updated_at'])
                                            )->format('%a days, %h hours');
                                            ?>
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
                                                <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $completion_time; ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-order" data-id="<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($order['tailor_notes']): ?>
                                                        <button class="btn btn-sm btn-outline-info view-notes" data-notes="<?php echo htmlspecialchars($order['tailor_notes']); ?>">
                                                            <i class="fas fa-sticky-note"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Completion Chart -->
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Completion by Order Type
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="completion-chart">
                                    <?php
                                    $order_types = [
                                        'set_baju_melayu' => 'Set Baju Melayu',
                                        'set_baju_kurung' => 'Set Baju Kurung',
                                        'set_baju_kebaya' => 'Set Baju Kebaya',
                                        'baju_kurta' => 'Baju Kurta',
                                        'repair' => 'Repair'
                                    ];
                                    
                                    foreach ($order_types as $type => $label):
                                        $count = count(array_filter($completed_orders, fn($o) => $o['order_type'] == $type));
                                        $percentage = $total_completed > 0 ? ($count / $total_completed) * 100 : 0;
                                    ?>
                                        <div class="chart-item mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="chart-label"><?php echo $label; ?></span>
                                                <span class="chart-value"><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%; background-color: <?php echo getChartColor($type); ?>"
                                                     aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-trophy me-2"></i>Performance Insights
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="performance-insights">
                                    <div class="insight-item mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-bolt text-warning me-3 fa-2x"></i>
                                            <div>
                                                <h6 class="mb-1">Fastest Completion</h6>
                                                <p class="mb-0 text-muted">
                                                    <?php
                                                    $fastest_order = null;
                                                    foreach ($completed_orders as $order) {
                                                        $completion_days = (strtotime($order['updated_at']) - strtotime($order['created_at'])) / (60 * 60 * 24);
                                                        if (!$fastest_order || $completion_days < $fastest_order['days']) {
                                                            $fastest_order = [
                                                                'order_id' => $order['order_id'],
                                                                'days' => $completion_days,
                                                                'type' => $order['order_type']
                                                            ];
                                                        }
                                                    }
                                                    echo $fastest_order ? number_format($fastest_order['days'], 1) . ' days (' . $fastest_order['order_id'] . ')' : 'N/A';
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="insight-item mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-star text-success me-3 fa-2x"></i>
                                            <div>
                                                <h6 class="mb-1">Most Common Order</h6>
                                                <p class="mb-0 text-muted">
                                                    <?php
                                                    $order_counts = [];
                                                    foreach ($completed_orders as $order) {
                                                        $order_counts[$order['order_type']] = ($order_counts[$order['order_type']] ?? 0) + 1;
                                                    }
                                                    arsort($order_counts);
                                                    $most_common = key($order_counts);
                                                    echo $most_common ? str_replace('_', ' ', $most_common) . ' (' . $order_counts[$most_common] . ' orders)' : 'N/A';
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="insight-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-check text-primary me-3 fa-2x"></i>
                                            <div>
                                                <h6 class="mb-1">This Month's Progress</h6>
                                                <p class="mb-0 text-muted">
                                                    <?php echo $month_completed; ?> orders completed this month
                                                    <?php if ($month_completed > 0): ?>
                                                        <br><small>Keep up the great work!</small>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
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

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel">Tailor Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notesContent">
                    <!-- Notes content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/tailor.js"></script>
</body>
</html>

<?php
// Helper function for chart colors
function getChartColor($order_type) {
    $colors = [
        'set_baju_melayu' => '#25344F',
        'set_baju_kurung' => '#617891',
        'set_baju_kebaya' => '#D5B893',
        'baju_kurta' => '#6F4D38',
        'repair' => '#632024'
    ];
    return $colors[$order_type] ?? '#6c757d';
}
?>