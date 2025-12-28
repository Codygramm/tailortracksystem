<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is tailor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tailor') {
    header('Location: ../login.php');
    exit();
}

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

$success = '';
$error = '';

// Handle status updates (non-AJAX fallback)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $tailor_notes = $_POST['tailor_notes'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, tailor_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND assigned_tailor = ?");
        $stmt->execute([$status, $tailor_notes, $order_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order status updated successfully!";
            // Redirect to prevent form resubmission
            header('Location: my_orders.php');
            exit();
        } else {
            $error = "Unable to update order status. Order not found or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Update order status error: " . $e->getMessage());
        $error = "Unable to update order status. Database error: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$query = "SELECT o.*, c.full_name as cashier_name 
          FROM orders o 
          JOIN users c ON o.created_by = c.user_id 
          WHERE o.assigned_tailor = ?";
$params = [$_SESSION['user_id']];

if ($status_filter && $status_filter != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY 
            CASE 
                WHEN o.status = 'in_progress' THEN 1
                WHEN o.status = 'assigned' THEN 2
                WHEN o.status = 'completed' THEN 3
                ELSE 4
            END, o.created_at DESC";

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
    <title>My Orders - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/tailor.css">
    <style>
        /* Additional styles for better UI */
        .order-card {
            border-left: 4px solid #6F4D38;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(111, 77, 56, 0.1);
        }
        
        .order-card .card-header {
            background: linear-gradient(135deg, #6F4D38 0%, #25344F 100%);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-outline-success {
            color: #198754;
            border-color: #198754;
        }
        
        .btn-outline-success:hover {
            background-color: #198754;
            color: white;
        }
        
        .work-notes {
            background: rgba(213, 184, 147, 0.1);
            border-left: 3px solid #D5B893;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .work-notes h6 {
            color: #6F4D38;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .work-notes p {
            font-size: 0.85rem;
            color: #617891;
            margin: 0;
        }
        
        /* Status badges */
        .badge.bg-info { background-color: #0dcaf0 !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529; }
        .badge.bg-success { background-color: #198754 !important; }
        .badge.bg-primary { background-color: #6F4D38 !important; }
        
        /* Filter buttons active state */
        .btn-primary {
            background-color: #6F4D38;
            border-color: #6F4D38;
        }
        
        .btn-outline-primary {
            color: #6F4D38;
            border-color: #6F4D38;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary.active {
            background-color: #6F4D38;
            border-color: #6F4D38;
            color: white;
        }
        
        /* Modal improvements */
        .modal-header {
            background: linear-gradient(135deg, #6F4D38 0%, #25344F 100%);
            color: white;
        }
        
        .modal-header .btn-close {
            filter: invert(1) brightness(2);
        }
        
        /* Toast styles */
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
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
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
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
                            <a class="nav-link active" href="my_orders.php">
                                <i class="fas fa-list-alt me-2"></i>
                                My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="completed_orders.php">
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
                                <h6><?php echo htmlspecialchars($_SESSION['full_name']); ?></h6>
                                <span class="badge bg-tailor">Tailor</span>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-list-alt me-2"></i>My Orders</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?status=all" class="btn btn-sm <?php echo (!$status_filter || $status_filter == 'all') ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?status=assigned" class="btn btn-sm <?php echo $status_filter == 'assigned' ? 'btn-primary' : 'btn-outline-primary'; ?>">Assigned</a>
                            <a href="?status=in_progress" class="btn btn-sm <?php echo $status_filter == 'in_progress' ? 'btn-primary' : 'btn-outline-primary'; ?>">In Progress</a>
                            <a href="?status=completed" class="btn btn-sm <?php echo $status_filter == 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Completed</a>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
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

                <!-- Orders List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Assigned Orders
                        </h5>
                        <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list text-muted fa-4x mb-3"></i>
                                <h4>No Orders Assigned</h4>
                                <p class="text-muted">You don't have any orders assigned to you yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($orders as $order): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card order-card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><i class="fas fa-receipt me-2"></i><?php echo htmlspecialchars($order['order_id']); ?></h6>
                                                <span class="badge bg-<?php echo getStatusBadge($order['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="text-primary">
                                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($order['customer_name']); ?>
                                                    </h6>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['customer_phone']); ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <span class="badge bg-light text-dark">
                                                            <i class="fas fa-tshirt me-1"></i>
                                                            <?php echo str_replace('_', ' ', $order['order_type']); ?>
                                                        </span>
                                                        <?php if ($order['order_type'] == 'repair' && $order['repair_type']): ?>
                                                            <small class="text-muted">(<?php echo htmlspecialchars($order['repair_type']); ?>)</small>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong><i class="fas fa-money-bill-wave me-1"></i>Amount:</strong> 
                                                        RM <?php echo number_format($order['total_amount'], 2); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong><i class="fas fa-user-tie me-1"></i>Assigned by:</strong> 
                                                        <?php echo htmlspecialchars($order['cashier_name']); ?>
                                                    </p>
                                                    <p class="mb-0">
                                                        <strong><i class="fas fa-calendar me-1"></i>Created:</strong> 
                                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                    </p>
                                                </div>
                                                
                                                <?php if (!empty($order['tailor_notes'])): ?>
                                                    <div class="work-notes">
                                                        <h6><i class="fas fa-sticky-note me-1"></i>Your Notes:</h6>
                                                        <p class="mb-0"><?php echo htmlspecialchars($order['tailor_notes']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline-primary btn-sm view-order" 
                                                            data-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </button>
                                                    <?php if ($order['status'] != 'completed'): ?>
                                                        <button class="btn btn-outline-success btn-sm update-status" 
                                                                data-id="<?php echo htmlspecialchars($order['order_id']); ?>"
                                                                data-status="<?php echo htmlspecialchars($order['status']); ?>"
                                                                data-notes="<?php echo htmlspecialchars($order['tailor_notes'] ?? ''); ?>">
                                                            <i class="fas fa-edit me-1"></i> Update Status
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-success btn-sm" disabled>
                                                            <i class="fas fa-check me-1"></i> Completed
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center stat-card">
                            <div class="card-body">
                                <i class="fas fa-list text-primary fa-2x mb-2"></i>
                                <h3 class="text-primary"><?php echo count($orders); ?></h3>
                                <p class="mb-0 text-muted">Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center stat-card">
                            <div class="card-body">
                                <i class="fas fa-user-clock text-info fa-2x mb-2"></i>
                                <h3 class="text-info"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'assigned')); ?></h3>
                                <p class="mb-0 text-muted">Assigned</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center stat-card">
                            <div class="card-body">
                                <i class="fas fa-tools text-warning fa-2x mb-2"></i>
                                <h3 class="text-warning"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'in_progress')); ?></h3>
                                <p class="mb-0 text-muted">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center stat-card">
                            <div class="card-body">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <h3 class="text-success"><?php echo count(array_filter($orders, fn($o) => $o['status'] == 'completed')); ?></h3>
                                <p class="mb-0 text-muted">Completed</p>
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
                    <h5 class="modal-title" id="orderModalLabel"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel"><i class="fas fa-edit me-2"></i>Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="my_orders.php" id="statusForm">
                    <div class="modal-body" id="statusModalBody">
                        <!-- Status form will be loaded here via JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_status">
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel"><i class="fas fa-sticky-note me-2"></i>Work Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notesContent">
                    <!-- Notes will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/tailor.js"></script>
    <script>
    // Enhanced JavaScript for handling status updates
    document.addEventListener('DOMContentLoaded', function() {
        // Handle status form submission
        const statusForm = document.getElementById('statusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', function(e) {
                // No need for AJAX - let it submit normally
                // The form will submit to my_orders.php which will handle it
            });
        }
    });
    
    // Show a simple toast notification
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0 mb-2`;
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
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
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