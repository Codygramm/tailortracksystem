<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
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
                <a class="nav-link <?php echo $current_page == 'register_staff.php' ? 'active' : ''; ?>" href="register_staff.php">
                    <i class="fas fa-user-plus me-2"></i>
                    Register Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage_staff.php' ? 'active' : ''; ?>" href="manage_staff.php">
                    <i class="fas fa-users me-2"></i>
                    Manage Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage_orders.php' ? 'active' : ''; ?>" href="manage_orders.php">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Manage Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
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