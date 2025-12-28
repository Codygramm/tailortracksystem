// Home page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Hero Slideshow Functionality
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    let slideInterval;

    function showSlide(index) {
        // Ensure index is within bounds
        if (index >= slides.length) {
            currentSlideIndex = 0;
        } else if (index < 0) {
            currentSlideIndex = slides.length - 1;
        } else {
            currentSlideIndex = index;
        }

        // Remove active class from all slides and indicators
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));

        // Add active class to current slide and indicator
        slides[currentSlideIndex].classList.add('active');
        indicators[currentSlideIndex].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlideIndex + 1);
    }

    function previousSlide() {
        showSlide(currentSlideIndex - 1);
    }

    // Auto-play slideshow
    function startSlideshow() {
        slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
    }

    function stopSlideshow() {
        clearInterval(slideInterval);
    }

    // Global functions for onclick handlers
    window.changeSlide = function(direction) {
        stopSlideshow();
        if (direction === 1) {
            nextSlide();
        } else {
            previousSlide();
        }
        startSlideshow();
    };

    window.currentSlide = function(index) {
        stopSlideshow();
        showSlide(index);
        startSlideshow();
    };

    // Start the slideshow
    startSlideshow();

    // Pause slideshow on hover
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', stopSlideshow);
        heroSection.addEventListener('mouseleave', startSlideshow);
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

    // Navbar background on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.backgroundColor = 'var(--primary)';
            navbar.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.backgroundColor = 'var(--primary)';
            navbar.style.boxShadow = 'none';
        }
    });

    // Order tracking form
    const trackingForm = document.getElementById('tracking-form');
    
    if (trackingForm) {
        trackingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const orderId = document.getElementById('orderId').value.trim();
            
            if (!orderId) {
                alert('Please enter an order ID');
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
            document.getElementById('tracking-result').scrollIntoView({ behavior: 'smooth' });
            
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
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> ${data.error}
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('orderId').focus()">
                                    Try Again
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    trackingStatus.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Connection Error:</strong> Unable to connect to server. Please try again later.
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="trackAnother()">
                                Try Again
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
                html += '<h6>Upper Body Measurements</h6><div class="row">';
                if (upper.shoulder) html += `<div class="col-6"><small><strong>Shoulder:</strong> ${escapeHtml(upper.shoulder)}"</small></div>`;
                if (upper.chest) html += `<div class="col-6"><small><strong>Chest:</strong> ${escapeHtml(upper.chest)}"</small></div>`;
                if (upper.waist) html += `<div class="col-6"><small><strong>Waist:</strong> ${escapeHtml(upper.waist)}"</small></div>`;
                if (upper.sleeve_length) html += `<div class="col-6"><small><strong>Sleeve:</strong> ${escapeHtml(upper.sleeve_length)}"</small></div>`;
                html += '</div>';
            }

            if (lower && Object.keys(lower).length > 1) {
                html += '<h6 class="mt-3">Lower Body Measurements</h6><div class="row">';
                if (lower.waist) html += `<div class="col-6"><small><strong>Waist:</strong> ${escapeHtml(lower.waist)}"</small></div>`;
                if (lower.hip) html += `<div class="col-6"><small><strong>Hip:</strong> ${escapeHtml(lower.hip)}"</small></div>`;
                if (lower.bottom_length) html += `<div class="col-6"><small><strong>Length:</strong> ${escapeHtml(lower.bottom_length)}"</small></div>`;
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
        
        let timelineHTML = '<div class="tracking-timeline">';
        timelineSteps.forEach(step => {
            const isActive = step.step <= order.active_steps;
            timelineHTML += `
                <div class="timeline-step ${isActive ? 'active' : ''}">
                    <div class="step-icon"><i class="fas fa-${step.icon}"></i></div>
                    <p>${step.label}</p>
                </div>
            `;
        });
        timelineHTML += '</div>';
        
        // Calculate balance
        const totalAmount = parseFloat(order.total_amount) || 0;
        const amountPaid = parseFloat(order.amount_paid) || 0;
        const balance = (totalAmount - amountPaid).toFixed(2);

        // Build the result HTML
        trackingStatus.innerHTML = `
            <div class="order-result">
                <div class="alert alert-${getStatusColor(order.status)}">
                    <h4 class="alert-heading">
                        <i class="fas fa-tshirt me-2"></i>Order: ${escapeHtml(order.order_id)}
                        <span class="badge bg-${getStatusColor(order.status)} float-end">${escapeHtml(order.status)}</span>
                    </h4>
                    <p class="mb-0"><strong>Customer:</strong> ${escapeHtml(order.customer_name)} | <strong>Phone:</strong> ${escapeHtml(order.customer_phone)}</p>
                </div>

                <div class="progress mb-4" style="height: 10px;">
                    <div class="progress-bar bg-${getStatusColor(order.status)}"
                         role="progressbar"
                         style="width: ${order.progress_percentage}%"
                         aria-valuenow="${order.progress_percentage}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>

                ${timelineHTML}

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle me-2"></i>Order Details</h5>
                        <table class="table table-sm">
                            <tr><td><strong>Type:</strong></td><td>${escapeHtml(order.order_type)}</td></tr>
                            ${order.repair_type ? `<tr><td><strong>Repair Type:</strong></td><td>${escapeHtml(order.repair_type)}</td></tr>` : ''}
                            <tr><td><strong>Total:</strong></td><td>RM ${order.total_amount}</td></tr>
                            <tr><td><strong>Paid:</strong></td><td>RM ${order.amount_paid}</td></tr>
                            <tr><td><strong>Balance:</strong></td><td>RM ${balance}</td></tr>
                            <tr><td><strong>Payment Status:</strong></td>
                                <td><span class="badge bg-${order.payment_status === 'Paid' ? 'success' : 'warning'}">${escapeHtml(order.payment_status)}</span></td></tr>
                            <tr><td><strong>Created:</strong></td><td>${escapeHtml(order.created_at)}</td></tr>
                            <tr><td><strong>Tailor:</strong></td><td>${escapeHtml(order.assigned_tailor)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-ruler-combined me-2"></i>Measurements</h5>
                        ${buildMeasurementsHTML(order.upper_measurements, order.lower_measurements)}
                    </div>
                </div>

                ${order.tailor_notes ? `
                <div class="alert alert-info mt-3">
                    <h6><i class="fas fa-sticky-note me-2"></i>Tailor Notes:</h6>
                    <p class="mb-0">${escapeHtml(order.tailor_notes)}</p>
                </div>
                ` : ''}

                <div class="text-center mt-4">
                    <button class="btn btn-outline-primary" onclick="printOrderDetails()">
                        <i class="fas fa-print me-1"></i> Print Details
                    </button>
                    <button class="btn btn-outline-secondary ms-2" onclick="trackAnother()">
                        <i class="fas fa-search me-1"></i> Track Another
                    </button>
                </div>
            </div>
        `;
    }
    
    // Contact form submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('contactSubmitBtn');
            const alertDiv = document.getElementById('contactFormAlert');
            const formData = new FormData(contactForm);

            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

            // Clear previous alerts
            alertDiv.innerHTML = '';

            // Send AJAX request
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alertDiv.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Success!</strong> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;

                    // Reset form
                    contactForm.reset();

                    // Scroll to alert
                    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    // Show error message
                    let errorMsg = 'Please correct the following errors:<ul class="mb-0 mt-2">';
                    data.errors.forEach(error => {
                        errorMsg += `<li>${error}</li>`;
                    });
                    errorMsg += '</ul>';

                    alertDiv.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Error!</strong> ${errorMsg}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error!</strong> Unable to send message. Please try again later or contact us directly at haikalsamsi07@gmail.com
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Message';
            });
        });
    }
    
    // Animation on scroll
    function animateOnScroll() {
        const elements = document.querySelectorAll('.service-card, .feature-item, .contact-item');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    }
    
    // Set initial state for animated elements
    document.querySelectorAll('.service-card, .feature-item, .contact-item').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    // Run once on load
    animateOnScroll();
    
    // Run on scroll
    window.addEventListener('scroll', animateOnScroll);
});

// Global functions
function printOrderDetails() {
    window.print();
}

function trackAnother() {
    document.getElementById('orderId').value = '';
    document.getElementById('tracking-result').style.display = 'none';
    document.getElementById('orderId').focus();
}