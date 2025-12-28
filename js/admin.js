// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Order view modal
    const viewOrderButtons = document.querySelectorAll('.view-order');
    const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    
    viewOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            loadOrderDetails(orderId);
        });
    });

     //Load order details via AJAX
    function loadOrderDetails(orderId) {
        const orderDetails = document.getElementById('orderDetails');
        orderDetails.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading order details...</p>
            </div>
        `;
        
        // In a real application, you would make an AJAX request here
        // For demo purposes, we'll simulate the response
 
    }

   // Load order details via AJAX
function loadOrderDetails(orderId) {
    const orderDetails = document.getElementById('orderDetails');
    orderDetails.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;
    
    // Make AJAX request to get real order details
    fetch(`../admin/get_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order);
            } else {
                orderDetails.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${data.error || 'Unable to load order details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            orderDetails.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading order details. Please try again.
                </div>
            `;
        });
}

// Display order details in modal
function displayOrderDetails(order) {
    const created = new Date(order.created_at);
    const formattedDate = created.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Build measurements HTML
    let measurementsHTML = '';
    
    // Upper body measurements
    if (order.shoulder || order.chest || order.upper_waist) {
        measurementsHTML += `
            <h6>Upper Body Measurements (inches)</h6>
            <table class="table table-sm">
                ${order.shoulder ? `<tr><td><strong>Shoulder:</strong></td><td>${order.shoulder} in</td></tr>` : ''}
                ${order.chest ? `<tr><td><strong>Chest:</strong></td><td>${order.chest} in</td></tr>` : ''}
                ${order.upper_waist ? `<tr><td><strong>Waist:</strong></td><td>${order.upper_waist} in</td></tr>` : ''}
                ${order.sleeve_length ? `<tr><td><strong>Sleeve Length:</strong></td><td>${order.sleeve_length} in</td></tr>` : ''}
                ${order.armhole ? `<tr><td><strong>Armhole:</strong></td><td>${order.armhole} in</td></tr>` : ''}
                ${order.wrist ? `<tr><td><strong>Wrist:</strong></td><td>${order.wrist} in</td></tr>` : ''}
                ${order.neck ? `<tr><td><strong>Neck:</strong></td><td>${order.neck} in</td></tr>` : ''}
                ${order.top_length ? `<tr><td><strong>Top Length:</strong></td><td>${order.top_length} in</td></tr>` : ''}
            </table>
        `;
    }
    
    // Lower body measurements
    if (order.lower_waist || order.hip || order.bottom_length) {
        measurementsHTML += `
            <h6>Lower Body Measurements (inches)</h6>
            <table class="table table-sm">
                ${order.lower_waist ? `<tr><td><strong>Waist:</strong></td><td>${order.lower_waist} in</td></tr>` : ''}
                ${order.hip ? `<tr><td><strong>Hip:</strong></td><td>${order.hip} in</td></tr>` : ''}
                ${order.bottom_length ? `<tr><td><strong>Bottom Length:</strong></td><td>${order.bottom_length} in</td></tr>` : ''}
                ${order.inseam ? `<tr><td><strong>Inseam:</strong></td><td>${order.inseam} in</td></tr>` : ''}
                ${order.outseam ? `<tr><td><strong>Outseam:</strong></td><td>${order.outseam} in</td></tr>` : ''}
            </table>
        `;
    }
    
    // If no measurements found
    if (!measurementsHTML) {
        measurementsHTML = `
            <div class="text-center py-3">
                <i class="fas fa-ruler-combined text-muted fa-2x mb-2"></i>
                <p class="text-muted">No measurement data available for this order.</p>
            </div>
        `;
    }
    
    const orderDetails = document.getElementById('orderDetails');
    orderDetails.innerHTML = `
        <div class="order-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Order ID:</strong></td>
                            <td>${order.order_id}</td>
                        </tr>
                        <tr>
                            <td><strong>Customer Name:</strong></td>
                            <td>${order.customer_name}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>${order.customer_phone}</td>
                        </tr>
                        ${order.customer_email ? `<tr>
                            <td><strong>Email:</strong></td>
                            <td>${order.customer_email}</td>
                        </tr>` : ''}
                        <tr>
                            <td><strong>Order Type:</strong></td>
                            <td>${order.order_type.replace(/_/g, ' ')}</td>
                        </tr>
                        ${order.repair_type ? `<tr>
                            <td><strong>Repair Type:</strong></td>
                            <td>${order.repair_type}</td>
                        </tr>` : ''}
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span class="badge bg-${getStatusBadge(order.status)}">${order.status.replace('_', ' ')}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Payment Status:</strong></td>
                            <td><span class="badge bg-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td>RM ${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                        ${order.assigned_tailor ? `<tr>
                            <td><strong>Assigned Tailor:</strong></td>
                            <td>${order.tailor_name || 'N/A'}</td>
                        </tr>` : ''}
                        <tr>
                            <td><strong>Created By:</strong></td>
                            <td>${order.cashier_name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>${formattedDate}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Measurement Details</h6>
                    ${measurementsHTML}
                </div>
            </div>
        </div>
    `;
    
    const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    orderModal.show();
}

// Helper function for status badges
function getStatusBadge(status) {
    switch (status) {
        case 'pending': return 'secondary';
        case 'assigned': return 'info';
        case 'in_progress': return 'warning';
        case 'completed': return 'success';
        case 'paid': return 'primary';
        default: return 'secondary';
    }
}

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

    // Sidebar toggle for mobile
    const sidebarToggle = document.querySelector('[data-bs-toggle="collapse"]');
    const sidebar = document.getElementById('sidebar');
    
    if (window.innerWidth < 768) {
        sidebar.classList.remove('show');
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.add('show');
        } else {
            sidebar.classList.remove('show');
        }
    });

    // Chart initialization (if needed)
    initializeCharts();
});

function initializeCharts() {
    // This would initialize any charts on the dashboard
    // For now, it's a placeholder for future chart implementations
    console.log('Charts would be initialized here');
}

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'custom-toast';
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.innerHTML = `
        <i class="fas fa-${getToastIcon(type)} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toastContainer.parentNode) {
            toastContainer.parentNode.removeChild(toastContainer);
        }
    }, 5000);
}

function getToastIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}
