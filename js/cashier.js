// Cashier Dashboard JavaScript
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

    // Fix for modal backdrop issue
    fixModalBackdrop();
});

// Fix modal backdrop issue
function fixModalBackdrop() {
    const orderModal = document.getElementById('orderModal');
    if (orderModal) {
        orderModal.addEventListener('hidden.bs.modal', function () {
            // Remove any lingering backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });

        orderModal.addEventListener('show.bs.modal', function () {
            // Ensure body has proper classes
            document.body.classList.add('modal-open');
        });
    }
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
    fetch(`../cashier/get_order_details.php?order_id=${orderId}`)
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

// Display order details in modal - Same as admin.js
function displayOrderDetails(order) {
    const created = new Date(order.created_at);
    const formattedDate = created.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Build measurements HTML - Same as admin.js
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
    
    // Proper modal handling
    const orderModalElement = document.getElementById('orderModal');
    const orderModal = new bootstrap.Modal(orderModalElement);
    
    // Add event listener to remove backdrop when modal is hidden
    orderModalElement.addEventListener('hidden.bs.modal', function () {
        // Remove backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // Remove modal-open class and reset styles
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
    
    orderModal.show();
}

// Helper function for status badges - Same as admin.js
function getStatusBadge(status) {
    switch (status) {
        case 'pending': return 'secondary';
        case 'assigned': return 'info';
        case 'in_progress': return 'warning';
        case 'completed': return 'success';
        case 'paid': return 'primary';
        case 'cancel': return 'danger';
        default: return 'secondary';
    }
}

// Toast notification function - Same as admin.js style
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

// Order type selection function
function selectOrderType(type) {
    // Remove selected class from all cards
    document.querySelectorAll('.order-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');
    
    // Store selected order type
    document.getElementById('order_type').value = type;
    
    // Show/hide measurement sections based on order type
    updateMeasurementSections(type);
}

// Update measurement sections based on order type
function updateMeasurementSections(orderType) {
    const upperBodySection = document.getElementById('upper-body-section');
    const lowerBodySection = document.getElementById('lower-body-section');
    const repairTypeSection = document.getElementById('repair-type-section');
    
    // Hide all sections first
    if (upperBodySection) upperBodySection.style.display = 'none';
    if (lowerBodySection) lowerBodySection.style.display = 'none';
    if (repairTypeSection) repairTypeSection.style.display = 'none';
    
    // Show sections based on order type
    switch(orderType) {
        case 'set_baju_melayu':
        case 'set_baju_kurung':
        case 'set_baju_kebaya':
            if (upperBodySection) upperBodySection.style.display = 'block';
            if (lowerBodySection) lowerBodySection.style.display = 'block';
            break;
        case 'baju_kurta':
            if (upperBodySection) upperBodySection.style.display = 'block';
            break;
        case 'repair':
            if (repairTypeSection) repairTypeSection.style.display = 'block';
            break;
    }
}

// Generate order ID
function generateOrderId() {
    const timestamp = new Date().getTime();
    const random = Math.floor(Math.random() * 1000);
    return `TT-${timestamp}-${random}`;
}

// Calculate total amount based on order type
function calculateTotalAmount(orderType) {
    const prices = {
        'set_baju_melayu': 150.00,
        'set_baju_kurung': 120.00,
        'set_baju_kebaya': 180.00,
        'baju_kurta': 80.00,
        'repair': 25.00
    };
    
    return prices[orderType] || 0;
}

// Print receipt
function printReceipt() {
    window.print();
}

// Download receipt as PDF
function downloadReceipt() {
    // In a real implementation, this would generate a PDF
    // For demo purposes, we'll show a toast
    showToast('Receipt download would be implemented here', 'info');
}

// Additional utility functions that might be needed
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR'
    }).format(amount);
}

// Form validation function
function validateOrderForm() {
    const customerName = document.getElementById('customer_name');
    const customerPhone = document.getElementById('customer_phone');
    const orderType = document.getElementById('order_type');
    
    if (!customerName.value.trim()) {
        showToast('Please enter customer name', 'error');
        customerName.focus();
        return false;
    }
    
    if (!customerPhone.value.trim()) {
        showToast('Please enter customer phone number', 'error');
        customerPhone.focus();
        return false;
    }
    
    if (!orderType.value) {
        showToast('Please select an order type', 'error');
        return false;
    }
    
    return true;
}

// Global function to manually remove modal backdrop (emergency fix)
function removeModalBackdrop() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Event delegation for dynamically created view order buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.view-order')) {
        const button = e.target.closest('.view-order');
        const orderId = button.getAttribute('data-id');
        if (orderId) {
            loadOrderDetails(orderId);
        }
    }

    // Handle notify customer button clicks
    if (e.target.closest('.notify-customer')) {
        const button = e.target.closest('.notify-customer');
        openNotifyModal(button);
    }
});

// Open notification modal with customer data
function openNotifyModal(button) {
    const orderId = button.getAttribute('data-id');
    const customerName = button.getAttribute('data-name');
    const customerPhone = button.getAttribute('data-phone');
    const customerEmail = button.getAttribute('data-email') || 'Not provided';
    const totalAmount = parseFloat(button.getAttribute('data-amount'));
    const amountPaid = parseFloat(button.getAttribute('data-paid'));
    const balance = totalAmount - amountPaid;

    // Populate modal fields
    document.getElementById('notifyCustomerName').textContent = customerName;
    document.getElementById('notifyCustomerPhone').textContent = customerPhone;
    document.getElementById('notifyCustomerEmail').textContent = customerEmail;
    document.getElementById('notifyOrderId').textContent = orderId;
    document.getElementById('notifyTotalAmount').textContent = totalAmount.toFixed(2);
    document.getElementById('notifyPaidAmount').textContent = amountPaid.toFixed(2);
    document.getElementById('notifyBalance').textContent = balance.toFixed(2);

    // Store data for sending
    document.getElementById('sendWhatsAppBtn').setAttribute('data-order-id', orderId);
    document.getElementById('sendWhatsAppBtn').setAttribute('data-customer-name', customerName);
    document.getElementById('sendWhatsAppBtn').setAttribute('data-customer-phone', customerPhone);
    document.getElementById('sendWhatsAppBtn').setAttribute('data-balance', balance.toFixed(2));
    document.getElementById('sendWhatsAppBtn').setAttribute('data-total', totalAmount.toFixed(2));

    document.getElementById('sendEmailBtn').setAttribute('data-order-id', orderId);
    document.getElementById('sendEmailBtn').setAttribute('data-customer-name', customerName);
    document.getElementById('sendEmailBtn').setAttribute('data-customer-email', customerEmail);
    document.getElementById('sendEmailBtn').setAttribute('data-total', totalAmount.toFixed(2));
    document.getElementById('sendEmailBtn').setAttribute('data-paid', amountPaid.toFixed(2));

    // Clear previous alerts
    document.getElementById('notifyAlert').innerHTML = '';

    // Show modal
    const notifyModal = new bootstrap.Modal(document.getElementById('notifyModal'));
    notifyModal.show();
}

// Send WhatsApp notification
document.addEventListener('DOMContentLoaded', function() {
    const whatsappBtn = document.getElementById('sendWhatsAppBtn');
    if (whatsappBtn) {
        whatsappBtn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const customerName = this.getAttribute('data-customer-name');
            const customerPhone = this.getAttribute('data-customer-phone');
            const balance = this.getAttribute('data-balance');
            const total = this.getAttribute('data-total');

            // Format customer phone number (remove non-digits and add country code if needed)
            let phone = customerPhone.replace(/\D/g, '');

            // Add Malaysia country code (60) if not present
            if (!phone.startsWith('60')) {
                if (phone.startsWith('0')) {
                    phone = '60' + phone.substring(1);
                } else {
                    phone = '60' + phone;
                }
            }

            // Create WhatsApp message to send to customer
            const message = `Hello ${customerName},

Good news! Your order is ready for collection! 🎉

📦 *Order Details*
Order ID: ${orderId}
Status: Completed & Ready

💰 *Payment Information*
Total Amount: RM ${total}
Balance Due: RM ${balance}

${parseFloat(balance) > 0 ?
`⚠️ *Please Note:* You have an outstanding balance of *RM ${balance}* to pay upon collection.` :
`✅ Your order is fully paid. No payment required upon collection.`}

📍 *Collection Details*
Location: WARISAN EWAN NIAGA RESOURCES
Address: Jalan Taib 3, Pontian District, Johor

🕒 *Operating Hours*
Monday - Saturday: 9:00 AM - 6:00 PM
Sunday: 10:00 AM - 4:00 PM

*What to Bring:*
• Order ID: ${orderId}
• Photo identification (IC/Driver's License)
${parseFloat(balance) > 0 ? `• Payment: RM ${balance}` : ''}

If you have any questions, feel free to reply to this message.

We look forward to seeing you soon!

Best regards,
WARISAN EWAN NIAGA RESOURCES Team`;

            // Open WhatsApp to CUSTOMER'S number with pre-filled message
            const whatsappURL = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappURL, '_blank');

            // Show success message
            showNotifyAlert(`WhatsApp opened to send message to customer: ${customerPhone}`, 'info');
        });
    }

    // Send Email notification
    const emailBtn = document.getElementById('sendEmailBtn');
    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const customerName = this.getAttribute('data-customer-name');
            const customerEmail = this.getAttribute('data-customer-email');
            const totalAmount = this.getAttribute('data-total');
            const amountPaid = this.getAttribute('data-paid');

            // Check if email is provided
            if (!customerEmail || customerEmail === 'Not provided') {
                showNotifyAlert('Customer email not available. Please collect email address first.', 'warning');
                return;
            }

            // Disable button and show loading
            emailBtn.disabled = true;
            emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

            // Prepare form data
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('customer_name', customerName);
            formData.append('customer_email', customerEmail);
            formData.append('total_amount', totalAmount);
            formData.append('amount_paid', amountPaid);

            // Send email via AJAX
            fetch('send_notification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotifyAlert(data.message, 'success');
                } else {
                    showNotifyAlert('Failed to send email: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotifyAlert('Error sending email. Please try again.', 'danger');
            })
            .finally(() => {
                // Re-enable button
                emailBtn.disabled = false;
                emailBtn.innerHTML = '<i class="fas fa-envelope me-2"></i>Send Email';
            });
        });
    }
});

// Show alert in notify modal
function showNotifyAlert(message, type) {
    const alertDiv = document.getElementById('notifyAlert');
    alertDiv.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getToastIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        const alert = alertDiv.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 10000);
}