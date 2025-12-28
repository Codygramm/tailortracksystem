// Tailor Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Order view modal
    const viewOrderButtons = document.querySelectorAll('.view-order');
    
    viewOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            loadOrderDetails(orderId);
        });
    });

    // Update status modal
    const updateStatusButtons = document.querySelectorAll('.update-status');
    
    updateStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const currentNotes = this.getAttribute('data-notes') || '';
            loadStatusForm(orderId, currentStatus, currentNotes);
        });
    });

    // View notes modal
    const viewNotesButtons = document.querySelectorAll('.view-notes');
    
    viewNotesButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notes = this.getAttribute('data-notes');
            showNotesModal(notes);
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
});

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
    fetch(`../tailor/get_order_details.php?order_id=${orderId}`)
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
    const updated = new Date(order.updated_at);
    const formattedCreated = created.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const formattedUpdated = updated.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Calculate completion time
    const completionTime = Math.round((updated - created) / (1000 * 60 * 60 * 24 * 100)) / 10; // in days
    
    // Build measurements HTML
    let measurementsHTML = '';
    
    // Upper body measurements
    if (order.shoulder || order.chest || order.upper_waist) {
        measurementsHTML += `
            <div class="measurement-section">
                <h6>Upper Body Measurements (inches)</h6>
                <div class="measurement-grid">
                    ${order.shoulder ? `<div class="measurement-item">
                        <span class="measurement-label">Shoulder</span>
                        <span class="measurement-value">${order.shoulder} in</span>
                    </div>` : ''}
                    ${order.chest ? `<div class="measurement-item">
                        <span class="measurement-label">Chest</span>
                        <span class="measurement-value">${order.chest} in</span>
                    </div>` : ''}
                    ${order.upper_waist ? `<div class="measurement-item">
                        <span class="measurement-label">Waist</span>
                        <span class="measurement-value">${order.upper_waist} in</span>
                    </div>` : ''}
                    ${order.sleeve_length ? `<div class="measurement-item">
                        <span class="measurement-label">Sleeve Length</span>
                        <span class="measurement-value">${order.sleeve_length} in</span>
                    </div>` : ''}
                    ${order.armhole ? `<div class="measurement-item">
                        <span class="measurement-label">Armhole</span>
                        <span class="measurement-value">${order.armhole} in</span>
                    </div>` : ''}
                    ${order.wrist ? `<div class="measurement-item">
                        <span class="measurement-label">Wrist</span>
                        <span class="measurement-value">${order.wrist} in</span>
                    </div>` : ''}
                    ${order.neck ? `<div class="measurement-item">
                        <span class="measurement-label">Neck</span>
                        <span class="measurement-value">${order.neck} in</span>
                    </div>` : ''}
                    ${order.top_length ? `<div class="measurement-item">
                        <span class="measurement-label">Top Length</span>
                        <span class="measurement-value">${order.top_length} in</span>
                    </div>` : ''}
                </div>
            </div>
        `;
    }
    
    // Lower body measurements
    if (order.lower_waist || order.hip || order.bottom_length) {
        measurementsHTML += `
            <div class="measurement-section">
                <h6>Lower Body Measurements (inches)</h6>
                <div class="measurement-grid">
                    ${order.lower_waist ? `<div class="measurement-item">
                        <span class="measurement-label">Waist</span>
                        <span class="measurement-value">${order.lower_waist} in</span>
                    </div>` : ''}
                    ${order.hip ? `<div class="measurement-item">
                        <span class="measurement-label">Hip</span>
                        <span class="measurement-value">${order.hip} in</span>
                    </div>` : ''}
                    ${order.bottom_length ? `<div class="measurement-item">
                        <span class="measurement-label">Bottom Length</span>
                        <span class="measurement-value">${order.bottom_length} in</span>
                    </div>` : ''}
                    ${order.inseam ? `<div class="measurement-item">
                        <span class="measurement-label">Inseam</span>
                        <span class="measurement-value">${order.inseam} in</span>
                    </div>` : ''}
                    ${order.outseam ? `<div class="measurement-item">
                        <span class="measurement-label">Outseam</span>
                        <span class="measurement-value">${order.outseam} in</span>
                    </div>` : ''}
                </div>
            </div>
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
    
    // Status progress
    const statusProgress = `
        <div class="status-progress mb-4">
            <div class="progress-step ${['assigned', 'in_progress', 'completed'].includes(order.status) ? 'completed' : ''}">
                <div class="step-indicator">1</div>
                <div class="step-label">Assigned</div>
            </div>
            <div class="progress-step ${['in_progress', 'completed'].includes(order.status) ? 'completed' : ''} ${order.status === 'in_progress' ? 'active' : ''}">
                <div class="step-indicator">2</div>
                <div class="step-label">In Progress</div>
            </div>
            <div class="progress-step ${order.status === 'completed' ? 'completed active' : ''}">
                <div class="step-indicator">3</div>
                <div class="step-label">Completed</div>
            </div>
        </div>
    `;

    const orderDetails = document.getElementById('orderDetails');
    orderDetails.innerHTML = `
        <div class="order-details">
            ${statusProgress}
            
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
                        <tr>
                            <td><strong>Assigned by:</strong></td>
                            <td>${order.cashier_name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>${formattedCreated}</td>
                        </tr>
                        ${order.status === 'completed' ? `<tr>
                            <td><strong>Completed:</strong></td>
                            <td>${formattedUpdated}</td>
                        </tr>` : ''}
                        ${order.status === 'completed' ? `<tr>
                            <td><strong>Completion Time:</strong></td>
                            <td>${completionTime} days</td>
                        </tr>` : ''}
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Measurement Details</h6>
                    <div class="measurement-display">
                        ${measurementsHTML}
                    </div>
                </div>
            </div>
            
            ${order.tailor_notes ? `
            <div class="work-notes">
                <h6>Your Notes:</h6>
                <p class="mb-0">${order.tailor_notes}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    orderModal.show();
}

// Load status form in modal
function loadStatusForm(orderId, currentStatus, currentNotes) {
    const statusModalBody = document.getElementById('statusModalBody');

    // Only load the form fields (form tag and buttons are in HTML now)
    const statusFormHTML = `
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="update_status" value="1">

        <div class="mb-3">
            <label for="status" class="form-label">Order Status *</label>
            <select class="form-select" id="status" name="status" required>
                <option value="assigned" ${currentStatus === 'assigned' ? 'selected' : ''}>Assigned</option>
                <option value="in_progress" ${currentStatus === 'in_progress' ? 'selected' : ''}>In Progress</option>
                <option value="completed" ${currentStatus === 'completed' ? 'selected' : ''}>Completed</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="tailor_notes" class="form-label">Work Notes (Optional)</label>
            <textarea class="form-control" id="tailor_notes" name="tailor_notes" rows="4"
                      placeholder="Add any notes about your work progress, challenges, or special requirements...">${currentNotes}</textarea>
            <div class="form-text">These notes will be visible to cashiers and administrators.</div>
        </div>

        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> When you mark an order as "Completed", it will be moved to the cashier for payment processing.
        </div>
    `;

    statusModalBody.innerHTML = statusFormHTML;

    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Show notes modal
function showNotesModal(notes) {
    const notesContent = document.getElementById('notesContent');
    notesContent.innerHTML = `
        <div class="work-notes">
            <h6>Tailor Work Notes:</h6>
            <p class="mb-0">${notes}</p>
        </div>
    `;
    
    const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
    notesModal.show();
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