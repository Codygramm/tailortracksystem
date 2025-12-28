// Tracking page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Order tracking form
    const trackingForm = document.getElementById('tracking-form');

    if (trackingForm) {
        trackingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const orderId = document.getElementById('orderId').value.trim();

            if (!orderId) {
                showAlert('Please enter an order ID', 'danger');
                return;
            }

            // Show loading
            const trackingStatus = document.querySelector('.tracking-status');
            trackingStatus.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mt-3">Searching for order...</h5>
                    <p class="text-muted">${orderId}</p>
                </div>
            `;

            document.getElementById('tracking-result').style.display = 'block';
            document.getElementById('tracking-result').scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Make AJAX request
            fetch(`track_order.php?order_id=${encodeURIComponent(orderId)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network error');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayOrderResult(data.order);
                    } else {
                        trackingStatus.innerHTML = `
                            <div class="alert alert-danger alert-lg">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Order Not Found</strong>
                                <p class="mb-3">${data.error}</p>
                                <hr>
                                <p class="mb-2"><strong>Please check:</strong></p>
                                <ul class="mb-3">
                                    <li>Your order ID is correct (format: TT-YYYYMMDD-XXX)</li>
                                    <li>The order was placed at our shop</li>
                                    <li>You received an order confirmation</li>
                                </ul>
                                <button class="btn btn-outline-danger" onclick="resetTracking()">
                                    <i class="fas fa-redo me-2"></i>Try Again
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    trackingStatus.innerHTML = `
                        <div class="alert alert-danger alert-lg">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Connection Error</strong>
                            <p class="mb-3">Unable to connect to the tracking server. Please check your internet connection and try again.</p>
                            <button class="btn btn-outline-danger" onclick="resetTracking()">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </button>
                        </div>
                    `;
                });
        });
    }

    function displayOrderResult(order) {
        const trackingStatus = document.querySelector('.tracking-status');

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Get badge color based on status
        function getStatusColor(status) {
            const statusLower = status.toLowerCase().replace(/\s+/g, '_');
            switch(statusLower) {
                case 'completed': return 'success';
                case 'in_progress': return 'warning';
                case 'assigned': return 'info';
                case 'paid': return 'primary';
                case 'pending': return 'secondary';
                default: return 'secondary';
            }
        }

        // Build measurements HTML
        function buildMeasurementsHTML(upper, lower) {
            let html = '';

            if (upper && Object.keys(upper).length > 1) {
                html += '<h6 class="mt-3"><i class="fas fa-ruler me-2"></i>Upper Body Measurements</h6><div class="row">';
                if (upper.shoulder) html += `<div class="col-6 col-md-3 mb-2"><small class="text-muted">Shoulder:</small><br><strong>${escapeHtml(upper.shoulder)}"</strong></div>`;
                if (upper.chest) html += `<div class="col-6 col-md-3 mb-2"><small class="text-muted">Chest:</small><br><strong>${escapeHtml(upper.chest)}"</strong></div>`;
                if (upper.waist) html += `<div class="col-6 col-md-3 mb-2"><small class="text-muted">Waist:</small><br><strong>${escapeHtml(upper.waist)}"</strong></div>`;
                if (upper.sleeve_length) html += `<div class="col-6 col-md-3 mb-2"><small class="text-muted">Sleeve:</small><br><strong>${escapeHtml(upper.sleeve_length)}"</strong></div>`;
                html += '</div>';
            }

            if (lower && Object.keys(lower).length > 1) {
                html += '<h6 class="mt-3"><i class="fas fa-ruler me-2"></i>Lower Body Measurements</h6><div class="row">';
                if (lower.waist) html += `<div class="col-6 col-md-4 mb-2"><small class="text-muted">Waist:</small><br><strong>${escapeHtml(lower.waist)}"</strong></div>`;
                if (lower.hip) html += `<div class="col-6 col-md-4 mb-2"><small class="text-muted">Hip:</small><br><strong>${escapeHtml(lower.hip)}"</strong></div>`;
                if (lower.bottom_length) html += `<div class="col-6 col-md-4 mb-2"><small class="text-muted">Length:</small><br><strong>${escapeHtml(lower.bottom_length)}"</strong></div>`;
                html += '</div>';
            }

            return html || '<p class="text-muted"><i>No measurements recorded</i></p>';
        }

        // Update timeline based on progress
        const timelineSteps = [
            { icon: 'receipt', label: 'Order Placed', step: 1 },
            { icon: 'user-check', label: 'Assigned', step: 2 },
            { icon: 'cut', label: 'In Progress', step: 3 },
            { icon: 'check-circle', label: 'Completed', step: 4 },
            { icon: 'money-bill-wave', label: 'Paid', step: 5 }
        ];

        let timelineHTML = '<div class="tracking-timeline row">';
        timelineSteps.forEach(step => {
            const isActive = step.step <= order.active_steps;
            timelineHTML += `
                <div class="col timeline-step ${isActive ? 'active' : ''}">
                    <div class="step-icon"><i class="fas fa-${step.icon}"></i></div>
                    <p class="step-label">${step.label}</p>
                </div>
            `;
        });
        timelineHTML += '</div>';

        // Calculate balance
        const totalAmount = parseFloat(order.total_amount) || 0;
        const amountPaid = parseFloat(order.amount_paid) || 0;
        const balance = (totalAmount - amountPaid).toFixed(2);

        // Build the result HTML with receipt-style design
        trackingStatus.innerHTML = `
            <div class="receipt-container">
                <div class="receipt order-result">
                    <!-- Company Header -->
                    <div class="receipt-header">
                        <div class="company-logo">
                            <img src="Asset/logo_receipt.png" alt="Company Logo" class="receipt-logo">
                        </div>
                        <h2 class="company-name">WARISAN EWAN NIAGA RESOURCES</h2>
                        <p class="company-address">Jalan Taib 3, Pontian District, Johor</p>
                        <p class="company-contact">Tel: 012-345 6789 | Email: info@tailortrack.com</p>
                        <div class="receipt-type-badge">
                            <span class="badge-order">ORDER TRACKING</span>
                        </div>
                    </div>

                    <!-- Receipt Info Bar -->
                    <div class="receipt-info-bar">
                        <div class="info-item">
                            <span class="info-label">Order ID:</span>
                            <span class="info-value"><strong>${escapeHtml(order.order_id)}</strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value">${escapeHtml(order.created_at)}</span>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div class="receipt-section status-section">
                        <h6 class="section-title">ORDER STATUS</h6>
                        <div class="status-display">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Current Status:</span>
                                <span class="badge bg-${getStatusColor(order.status)} badge-lg">
                                    ${escapeHtml(order.status)}
                                </span>
                            </div>
                            <div class="progress" style="height: 20px; border-radius: 10px;">
                                <div class="progress-bar bg-${getStatusColor(order.status)}"
                                     role="progressbar"
                                     style="width: ${order.progress_percentage}%"
                                     aria-valuenow="${order.progress_percentage}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    ${order.progress_percentage}% Complete
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details Section -->
                    <div class="receipt-section">
                        <h6 class="section-title">CUSTOMER DETAILS</h6>
                        <table class="details-table">
                            <tr>
                                <td class="label-col">Customer Name:</td>
                                <td class="value-col">${escapeHtml(order.customer_name)}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Phone Number:</td>
                                <td class="value-col">${escapeHtml(order.customer_phone)}</td>
                            </tr>
                            ${order.customer_email ? `
                            <tr>
                                <td class="label-col">Email:</td>
                                <td class="value-col">${escapeHtml(order.customer_email)}</td>
                            </tr>` : ''}
                        </table>
                    </div>

                    <!-- Order Details Section -->
                    <div class="receipt-section">
                        <h6 class="section-title">ORDER DETAILS</h6>
                        <table class="details-table">
                            <tr>
                                <td class="label-col">Order Type:</td>
                                <td class="value-col">${escapeHtml(order.order_type)}</td>
                            </tr>
                            ${order.repair_type ? `
                            <tr>
                                <td class="label-col">Repair Type:</td>
                                <td class="value-col">${escapeHtml(order.repair_type)}</td>
                            </tr>` : ''}
                            <tr>
                                <td class="label-col">Assigned Tailor:</td>
                                <td class="value-col">${escapeHtml(order.assigned_tailor)}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Payment Summary Section -->
                    <div class="receipt-section payment-section">
                        <h6 class="section-title">PAYMENT SUMMARY</h6>
                        <table class="payment-table">
                            <tr>
                                <td class="label-col">Total Order Amount:</td>
                                <td class="amount-col">RM ${order.total_amount}</td>
                            </tr>
                            <tr class="deposit-row">
                                <td class="label-col">Amount Paid:</td>
                                <td class="amount-col">RM ${order.amount_paid}</td>
                            </tr>
                            <tr class="${balance > 0 ? 'balance-row' : 'total-row'}">
                                <td class="label-col"><strong>${balance > 0 ? 'Balance Due:' : 'Status:'}</strong></td>
                                <td class="amount-col ${balance > 0 ? '' : 'status-paid'}">
                                    <strong>${balance > 0 ? 'RM ' + balance : 'PAID IN FULL'}</strong>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Measurements Section -->
                    <div class="receipt-section">
                        <h6 class="section-title">MEASUREMENTS</h6>
                        ${buildMeasurementsHTML(order.upper_measurements, order.lower_measurements)}
                    </div>

                    ${order.tailor_notes ? `
                    <!-- Tailor Notes Section -->
                    <div class="receipt-section">
                        <h6 class="section-title">TAILOR NOTES</h6>
                        <p class="mb-0">${escapeHtml(order.tailor_notes)}</p>
                    </div>
                    ` : ''}

                    <!-- Footer -->
                    <div class="receipt-footer">
                        <div class="footer-divider"></div>
                        <div class="footer-notes">
                            <p><i class="fas fa-info-circle"></i> <strong>Order Progress:</strong> ${order.progress_percentage}% complete. ${order.status === 'Completed' || order.status === 'Paid' ? 'Ready for pickup!' : 'We will notify you when your order is ready.'}</p>
                        </div>
                        <div class="footer-thank-you">
                            <p>Thank you for choosing our services!</p>
                            <p class="mt-3">For inquiries, please contact us at 012-345 6789</p>
                        </div>
                        <div class="footer-powered">
                            <small>Powered by TailorTrack System</small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons (hidden in print) -->
                <div class="text-center mt-4 no-print">
                    <button class="btn btn-primary me-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Details
                    </button>
                    <button class="btn btn-outline-secondary" onclick="resetTracking()">
                        <i class="fas fa-search me-2"></i> Track Another Order
                    </button>
                </div>
            </div>
        `;
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Global functions
function resetTracking() {
    document.getElementById('orderId').value = '';
    document.getElementById('tracking-result').style.display = 'none';
    document.getElementById('orderId').focus();
    window.scrollTo({
        top: document.querySelector('.tracking-section').offsetTop - 80,
        behavior: 'smooth'
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const form = document.getElementById('tracking-form');
    form.parentNode.insertBefore(alertDiv, form);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
