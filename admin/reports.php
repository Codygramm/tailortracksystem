<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get report parameters
$report_type = $_GET['report_type'] ?? 'orders_summary';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Initialize data arrays
$report_data = [];
$orders_by_type = [];
$orders_by_status = [];
$staff_data = [];
$financial_data = [];

try {
    if ($report_type === 'orders_summary') {
        // 1. Get Summary Statistics
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$date_from, $date_to]);
        $report_data = $stmt->fetch();

        // 2. Get Orders by Type
        $stmt = $pdo->prepare("
            SELECT
                order_type,
                COUNT(*) as count,
                SUM(total_amount) as revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY order_type
            ORDER BY count DESC
        ");
        $stmt->execute([$date_from, $date_to]);
        $orders_by_type = $stmt->fetchAll();

        // 3. Get Orders by Status
        $stmt = $pdo->prepare("
            SELECT
                status,
                COUNT(*) as count
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC
        ");
        $stmt->execute([$date_from, $date_to]);
        $orders_by_status = $stmt->fetchAll();

    } elseif ($report_type === 'staff_performance') {
        // Get Staff Performance Data
        $stmt = $pdo->prepare("
            SELECT
                u.user_id,
                u.full_name,
                u.role,
                COUNT(CASE WHEN u.role = 'cashier' THEN o.order_id END) as orders_created,
                COUNT(CASE WHEN u.role = 'tailor' AND o.assigned_tailor = u.user_id THEN o.order_id END) as orders_assigned,
                COUNT(CASE WHEN u.role = 'tailor' AND o.assigned_tailor = u.user_id AND o.status = 'completed' THEN 1 END) as orders_completed,
                SUM(CASE WHEN u.role = 'cashier' THEN o.total_amount ELSE 0 END) as revenue_generated
            FROM users u
            LEFT JOIN orders o ON (
                (u.role = 'cashier' AND o.created_by = u.user_id) OR
                (u.role = 'tailor' AND o.assigned_tailor = u.user_id)
            ) AND DATE(o.created_at) BETWEEN ? AND ?
            WHERE u.role IN ('cashier', 'tailor')
            GROUP BY u.user_id, u.full_name, u.role
            ORDER BY u.role, u.full_name
        ");
        $stmt->execute([$date_from, $date_to]);
        $staff_data = $stmt->fetchAll();

    } elseif ($report_type === 'financial') {
        // Get Financial Data by Day
        $stmt = $pdo->prepare("
            SELECT
                DATE(created_at) as order_date,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status != 'paid' AND status != 'cancel' THEN total_amount ELSE 0 END) as pending_amount
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY order_date
        ");
        $stmt->execute([$date_from, $date_to]);
        $financial_data = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Report error: " . $e->getMessage());
    $error = "Unable to generate report. Please try again.";
}

// Helper function to format order type name
function formatOrderType($type) {
    return ucwords(str_replace('_', ' ', $type));
}

// Helper function to get status badge color
function getStatusColor($status) {
    $colors = [
        'pending' => 'secondary',
        'assigned' => 'info',
        'in_progress' => 'warning',
        'completed' => 'success',
        'paid' => 'primary',
        'cancel' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

// Helper function to get role badge color
function getRoleColor($role) {
    $colors = [
        'admin' => 'primary',
        'cashier' => 'info',
        'tailor' => 'warning'
    ];
    return $colors[$role] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - TailorTrack Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../Asset/logo_icon.png" alt="TailorTrack" style="width: 30px; height: 30px; object-fit: contain;" class="me-2">
                <span>TailorTrack Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
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

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h1>
                    <div class="btn-group">
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print Report
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Report Filters -->
                <div class="card no-print mb-4">
                    <div class="card-body">
                        <form method="GET" action="reports.php" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="report_type" class="form-label"><i class="fas fa-file-alt me-1"></i>Report Type</label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="orders_summary" <?php echo $report_type === 'orders_summary' ? 'selected' : ''; ?>>Orders Summary</option>
                                    <option value="staff_performance" <?php echo $report_type === 'staff_performance' ? 'selected' : ''; ?>>Staff Performance</option>
                                    <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label"><i class="fas fa-calendar-alt me-1"></i>From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label"><i class="fas fa-calendar-alt me-1"></i>To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sync-alt me-1"></i> Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Header (Print Only) -->
                <div class="print-only text-center mb-4" style="display: none;">
                    <img src="../Asset/logo_receipt.png" alt="TailorTrack" style="width: 100px; height: 100px; object-fit: contain;">
                    <h2 class="mt-3">TailorTrack</h2>
                    <p class="text-muted">Professional Tailoring Services</p>
                    <h4 class="mt-4">
                        <?php
                        $titles = [
                            'orders_summary' => 'Orders Summary Report',
                            'staff_performance' => 'Staff Performance Report',
                            'financial' => 'Financial Report'
                        ];
                        echo $titles[$report_type];
                        ?>
                    </h4>
                    <p class="text-muted">
                        Period: <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?>
                    </p>
                    <p class="text-muted">Generated on: <?php echo date('d M Y, g:i A'); ?></p>
                    <hr>
                </div>

                <!-- ORDERS SUMMARY REPORT -->
                <?php if ($report_type === 'orders_summary'): ?>
                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Orders</h6>
                                            <h2 class="mb-0"><?php echo $report_data['total_orders'] ?? 0; ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Revenue</h6>
                                            <h2 class="mb-0">RM <?php echo number_format($report_data['total_revenue'] ?? 0, 2); ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Completed</h6>
                                            <h2 class="mb-0"><?php echo $report_data['completed_orders'] ?? 0; ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Avg Order Value</h6>
                                            <h2 class="mb-0">RM <?php echo number_format($report_data['avg_order_value'] ?? 0, 2); ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Orders by Type Pie Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Orders by Type
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($orders_by_type)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No order data available for this period</p>
                                        </div>
                                    <?php else: ?>
                                        <canvas id="orderTypeChart" height="280"></canvas>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Orders by Status Pie Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Orders by Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($orders_by_status)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No order data available for this period</p>
                                        </div>
                                    <?php else: ?>
                                        <canvas id="orderStatusChart" height="280"></canvas>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Tables Row -->
                    <div class="row">
                        <!-- Orders by Type Table -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-table me-2"></i>Orders by Type Details
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order Type</th>
                                                    <th class="text-center">Count</th>
                                                    <th class="text-end">Revenue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($orders_by_type)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            No data available
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($orders_by_type as $type): ?>
                                                        <tr>
                                                            <td><strong><?php echo formatOrderType($type['order_type']); ?></strong></td>
                                                            <td class="text-center">
                                                                <span class="badge bg-primary"><?php echo $type['count']; ?></span>
                                                            </td>
                                                            <td class="text-end">RM <?php echo number_format($type['revenue'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL</td>
                                                        <td class="text-center">
                                                            <?php echo array_sum(array_column($orders_by_type, 'count')); ?>
                                                        </td>
                                                        <td class="text-end">
                                                            RM <?php echo number_format(array_sum(array_column($orders_by_type, 'revenue')), 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders by Status Table -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-table me-2"></i>Orders by Status Details
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th class="text-center">Count</th>
                                                    <th class="text-end">Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($orders_by_status)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            No data available
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php
                                                    $total_orders = array_sum(array_column($orders_by_status, 'count'));
                                                    foreach ($orders_by_status as $status):
                                                        $percentage = ($status['count'] / $total_orders) * 100;
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-<?php echo getStatusColor($status['status']); ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <strong><?php echo $status['count']; ?></strong>
                                                            </td>
                                                            <td class="text-end"><?php echo number_format($percentage, 1); ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL</td>
                                                        <td class="text-center"><?php echo $total_orders; ?></td>
                                                        <td class="text-end">100.0%</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- STAFF PERFORMANCE REPORT -->
                <?php elseif ($report_type === 'staff_performance'): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>Staff Performance Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($staff_data)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No staff data available for this period</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Staff Name</th>
                                                        <th class="text-center">Role</th>
                                                        <th class="text-center">Orders Created</th>
                                                        <th class="text-center">Orders Assigned</th>
                                                        <th class="text-center">Orders Completed</th>
                                                        <th class="text-end">Revenue Generated</th>
                                                        <th class="text-center">Performance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($staff_data as $staff): ?>
                                                        <?php
                                                        $completion_rate = 0;
                                                        if ($staff['role'] === 'tailor' && $staff['orders_assigned'] > 0) {
                                                            $completion_rate = ($staff['orders_completed'] / $staff['orders_assigned']) * 100;
                                                        } elseif ($staff['role'] === 'cashier' && $staff['orders_created'] > 0) {
                                                            $completion_rate = 100; // Cashiers always 100% for creating orders
                                                        }
                                                        $performance_class = $completion_rate >= 80 ? 'success' : ($completion_rate >= 60 ? 'warning' : 'danger');
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="staff-avatar me-2">
                                                                        <i class="fas fa-user"></i>
                                                                    </div>
                                                                    <strong><?php echo htmlspecialchars($staff['full_name']); ?></strong>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-<?php echo getRoleColor($staff['role']); ?>">
                                                                    <?php echo ucfirst($staff['role']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php echo $staff['orders_created'] > 0 ? $staff['orders_created'] : '-'; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php echo $staff['orders_assigned'] > 0 ? $staff['orders_assigned'] : '-'; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php echo $staff['orders_completed'] > 0 ? $staff['orders_completed'] : '-'; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                RM <?php echo number_format($staff['revenue_generated'], 2); ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-<?php echo $performance_class; ?>">
                                                                    <?php echo number_format($completion_rate, 1); ?>%
                                                                </span>
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
                    </div>

                    <!-- Staff Charts -->
                    <?php if (!empty($staff_data)): ?>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Orders by Staff
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="staffOrdersChart" height="280"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Revenue by Staff
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="staffRevenueChart" height="280"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                <!-- FINANCIAL REPORT -->
                <?php elseif ($report_type === 'financial'): ?>
                    <?php
                    $total_revenue = 0;
                    $total_paid = 0;
                    $total_pending = 0;
                    $total_orders_count = 0;

                    foreach ($financial_data as $day) {
                        $total_revenue += $day['daily_revenue'];
                        $total_paid += $day['paid_amount'];
                        $total_pending += $day['pending_amount'];
                        $total_orders_count += $day['order_count'];
                    }
                    ?>

                    <!-- Financial Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Revenue</h6>
                                            <h2 class="mb-0">RM <?php echo number_format($total_revenue, 2); ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Paid Amount</h6>
                                            <h2 class="mb-0 text-success">RM <?php echo number_format($total_paid, 2); ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Pending Amount</h6>
                                            <h2 class="mb-0 text-warning">RM <?php echo number_format($total_pending, 2); ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-1">Total Orders</h6>
                                            <h2 class="mb-0"><?php echo $total_orders_count; ?></h2>
                                        </div>
                                        <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Trend Chart -->
                    <?php if (!empty($financial_data)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Revenue Trend
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="financialChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Financial Details Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-table me-2"></i>Daily Financial Details
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($financial_data)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No financial data available for this period</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th class="text-center">Orders</th>
                                                        <th class="text-end">Daily Revenue</th>
                                                        <th class="text-end">Paid Amount</th>
                                                        <th class="text-end">Pending Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($financial_data as $day): ?>
                                                        <tr>
                                                            <td><strong><?php echo date('d M Y', strtotime($day['order_date'])); ?></strong></td>
                                                            <td class="text-center">
                                                                <span class="badge bg-primary"><?php echo $day['order_count']; ?></span>
                                                            </td>
                                                            <td class="text-end">RM <?php echo number_format($day['daily_revenue'], 2); ?></td>
                                                            <td class="text-end text-success">RM <?php echo number_format($day['paid_amount'], 2); ?></td>
                                                            <td class="text-end text-warning">RM <?php echo number_format($day['pending_amount'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL</td>
                                                        <td class="text-center"><?php echo $total_orders_count; ?></td>
                                                        <td class="text-end">RM <?php echo number_format($total_revenue, 2); ?></td>
                                                        <td class="text-end text-success">RM <?php echo number_format($total_paid, 2); ?></td>
                                                        <td class="text-end text-warning">RM <?php echo number_format($total_pending, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Report Footer -->
                <div class="card mb-4 mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Report Information</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-file-alt me-2 text-primary"></i><strong>Report Type:</strong>
                                        <?php
                                        $report_names = [
                                            'orders_summary' => 'Orders Summary',
                                            'staff_performance' => 'Staff Performance',
                                            'financial' => 'Financial Report'
                                        ];
                                        echo $report_names[$report_type];
                                        ?>
                                    </li>
                                    <li><i class="fas fa-calendar me-2 text-primary"></i><strong>Period:</strong>
                                        <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?>
                                    </li>
                                    <li><i class="fas fa-clock me-2 text-primary"></i><strong>Generated:</strong>
                                        <?php echo date('d M Y, g:i A'); ?>
                                    </li>
                                    <li><i class="fas fa-user me-2 text-primary"></i><strong>Generated By:</strong>
                                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6 text-end">
                                <img src="../Asset/logo_receipt.png" alt="TailorTrack" style="width: 80px; height: 80px; object-fit: contain; opacity: 0.6;">
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart Colors Configuration
        const chartColors = {
            orderTypes: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'],
            statuses: {
                'pending': '#6c757d',
                'assigned': '#0dcaf0',
                'in_progress': '#ffc107',
                'completed': '#198754',
                'paid': '#0d6efd',
                'cancel': '#dc3545'
            },
            staff: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6c757d', '#ff6384', '#36a2eb'],
            financial: {
                revenue: '#0d6efd',
                paid: '#198754',
                pending: '#ffc107'
            }
        };

        // Initialize Charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const reportType = '<?php echo $report_type; ?>';

            if (reportType === 'orders_summary') {
                initOrdersCharts();
            } else if (reportType === 'staff_performance') {
                initStaffCharts();
            } else if (reportType === 'financial') {
                initFinancialChart();
            }
        });

        function initOrdersCharts() {
            // Orders by Type Pie Chart
            <?php if (!empty($orders_by_type)): ?>
            const orderTypeCanvas = document.getElementById('orderTypeChart');
            if (orderTypeCanvas) {
                new Chart(orderTypeCanvas, {
                    type: 'pie',
                    data: {
                        labels: [<?php echo implode(',', array_map(function($t) { return '"' . formatOrderType($t['order_type']) . '"'; }, $orders_by_type)); ?>],
                        datasets: [{
                            data: [<?php echo implode(',', array_column($orders_by_type, 'count')); ?>],
                            backgroundColor: chartColors.orderTypes,
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 }, usePointStyle: true } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return label + ': ' + value + ' orders (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            // Orders by Status Pie Chart
            <?php if (!empty($orders_by_status)): ?>
            const orderStatusCanvas = document.getElementById('orderStatusChart');
            if (orderStatusCanvas) {
                const statusData = <?php echo json_encode($orders_by_status); ?>;
                const statusLabels = statusData.map(item => item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
                const statusCounts = statusData.map(item => item.count);
                const statusColors = statusData.map(item => chartColors.statuses[item.status] || '#6c757d');

                new Chart(orderStatusCanvas, {
                    type: 'pie',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusCounts,
                            backgroundColor: statusColors,
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 }, usePointStyle: true } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return label + ': ' + value + ' orders (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        }

        function initStaffCharts() {
            <?php if (!empty($staff_data)): ?>
            const staffData = <?php echo json_encode($staff_data); ?>;
            const staffNames = staffData.map(s => s.full_name);
            const ordersCreated = staffData.map(s => parseInt(s.orders_created));
            const ordersAssigned = staffData.map(s => parseInt(s.orders_assigned));
            const revenueGenerated = staffData.map(s => parseFloat(s.revenue_generated));

            // Orders by Staff Chart
            const staffOrdersCanvas = document.getElementById('staffOrdersChart');
            if (staffOrdersCanvas) {
                new Chart(staffOrdersCanvas, {
                    type: 'bar',
                    data: {
                        labels: staffNames,
                        datasets: [{
                            label: 'Orders Created',
                            data: ordersCreated,
                            backgroundColor: chartColors.financial.revenue,
                            borderRadius: 5
                        }, {
                            label: 'Orders Assigned',
                            data: ordersAssigned,
                            backgroundColor: chartColors.financial.pending,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            // Revenue by Staff Chart
            const staffRevenueCanvas = document.getElementById('staffRevenueChart');
            if (staffRevenueCanvas) {
                new Chart(staffRevenueCanvas, {
                    type: 'bar',
                    data: {
                        labels: staffNames,
                        datasets: [{
                            label: 'Revenue (RM)',
                            data: revenueGenerated,
                            backgroundColor: chartColors.financial.paid,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
            <?php endif; ?>
        }

        function initFinancialChart() {
            <?php if (!empty($financial_data)): ?>
            const financialData = <?php echo json_encode($financial_data); ?>;
            const dates = financialData.map(d => {
                const date = new Date(d.order_date);
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            });
            const dailyRevenue = financialData.map(d => parseFloat(d.daily_revenue));
            const paidAmount = financialData.map(d => parseFloat(d.paid_amount));
            const pendingAmount = financialData.map(d => parseFloat(d.pending_amount));

            const financialCanvas = document.getElementById('financialChart');
            if (financialCanvas) {
                new Chart(financialCanvas, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Daily Revenue',
                            data: dailyRevenue,
                            borderColor: chartColors.financial.revenue,
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Paid Amount',
                            data: paidAmount,
                            borderColor: chartColors.financial.paid,
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Pending Amount',
                            data: pendingAmount,
                            borderColor: chartColors.financial.pending,
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 15 } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': RM ' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: function(value) { return 'RM ' + value.toFixed(2); } }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        }

        // Export to Excel function
        function exportToExcel() {
            const reportType = '<?php echo $report_type; ?>';
            const dateFrom = '<?php echo $date_from; ?>';
            const dateTo = '<?php echo $date_to; ?>';

            // Build export URL
            const exportUrl = `export_report_excel.php?report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;

            // Open in new window to trigger download
            window.location.href = exportUrl;
        }
    </script>
</body>
</html>
