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

// Handle staff deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = "Staff member deleted successfully!";
            } else {
                $error = "Staff member not found!";
            }
        } catch (PDOException $e) {
            error_log("Delete staff error: " . $e->getMessage());
            $error = "Unable to delete staff member. They may have associated orders.";
        }
    }
}

// Get all staff members
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
    $staff_members = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch staff error: " . $e->getMessage());
    $error = "Unable to load staff data.";
}

// Get current page for sidebar active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - TailorTrack</title>
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

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Staff</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="register_staff.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Add New Staff
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Staff Members
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="staffTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($staff_members)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No staff members found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <tr>
                                                <td><?php echo $staff['user_id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="staff-avatar me-2">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($staff['full_name']); ?></strong>
                                                            <?php if ($staff['user_id'] == $_SESSION['user_id']): ?>
                                                                <span class="badge bg-primary ms-1">You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $staff['role']; ?>">
                                                        <?php echo ucfirst($staff['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($staff['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary view-staff" data-id="<?php echo $staff['user_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-secondary edit-staff" 
                                                                data-id="<?php echo $staff['user_id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($staff['username']); ?>"
                                                                data-role="<?php echo $staff['role']; ?>"
                                                                data-fullname="<?php echo htmlspecialchars($staff['full_name']); ?>"
                                                                data-email="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>"
                                                                data-phone="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($staff['user_id'] != $_SESSION['user_id']): ?>
                                                            <button class="btn btn-outline-danger delete-staff" data-id="<?php echo $staff['user_id']; ?>" data-name="<?php echo htmlspecialchars($staff['full_name']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-outline-danger" disabled>
                                                                <i class="fas fa-trash"></i>
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

                <!-- Staff Summary -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count(array_filter($staff_members, fn($s) => $s['role'] == 'admin')); ?></h3>
                                <p class="mb-0">Administrators</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo count(array_filter($staff_members, fn($s) => $s['role'] == 'cashier')); ?></h3>
                                <p class="mb-0">Cashiers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count(array_filter($staff_members, fn($s) => $s['role'] == 'tailor')); ?></h3>
                                <p class="mb-0">Tailors</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Staff Details Modal -->
    <div class="modal fade" id="staffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="staffDetails">
                    <!-- Staff details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm" method="POST" action="update_staff.php">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                            <div class="form-text">Unique username for login</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="cashier">Cashier</option>
                                <option value="tailor">Tailor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStaffChanges">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteStaffName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Delete Staff</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
    <script>
        // Staff view modal
        document.querySelectorAll('.view-staff').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.getAttribute('data-id');
                document.getElementById('staffDetails').innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading staff details...</p>
                    </div>
                `;
                
                const staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
                staffModal.show();
                
                // Simulate AJAX call
                setTimeout(() => {
                    document.getElementById('staffDetails').innerHTML = `
                        <p><strong>Staff ID:</strong> ${staffId}</p>
                        <p><strong>Full Name:</strong> Staff Member ${staffId}</p>
                        <p><strong>Role:</strong> Cashier</p>
                        <p><strong>Email:</strong> staff${staffId}@tailortrack.com</p>
                        <p><strong>Phone:</strong> 012-345 6789</p>
                        <p><strong>Joined:</strong> March 15, 2024</p>
                    `;
                }, 1000);
            });
        });

        // Edit staff modal
        document.querySelectorAll('.edit-staff').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const role = this.getAttribute('data-role');
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const phone = this.getAttribute('data-phone');
                
                // Populate the form
                document.getElementById('edit_user_id').value = staffId;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_role').value = role;
                document.getElementById('edit_full_name').value = fullName;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_phone').value = phone;
                document.getElementById('edit_password').value = '';
                
                const editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
                editModal.show();
            });
        });

        // Save staff changes with AJAX
        document.getElementById('saveStaffChanges').addEventListener('click', function() {
            const form = document.getElementById('editStaffForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!formData.get('username') || !formData.get('role') || !formData.get('full_name')) {
                alert('Please fill in all required fields!');
                return;
            }
            
            // Password validation if provided
            const password = formData.get('password');
            if (password && password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return;
            }
            
            // Show loading state
            const saveButton = this;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveButton.disabled = true;
            
            // Send AJAX request
            fetch('update_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    
                    // Close modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editStaffModal'));
                    editModal.hide();
                    
                    // Reload page after a short delay to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating staff.', 'error');
            })
            .finally(() => {
                // Reset button state
                saveButton.innerHTML = 'Save Changes';
                saveButton.disabled = false;
            });
        });

        // Delete confirmation
        document.querySelectorAll('.delete-staff').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.getAttribute('data-id');
                const staffName = this.getAttribute('data-name');
                
                document.getElementById('deleteStaffName').textContent = staffName;
                document.getElementById('confirmDelete').href = `manage_staff.php?delete=${staffId}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1055';
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0`;
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
            document.body.appendChild(toastContainer);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove from DOM after hide
            toast.addEventListener('hidden.bs.toast', function() {
                toastContainer.remove();
            });
        }
    </script>
</body>
</html>