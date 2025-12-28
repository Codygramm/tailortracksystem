// Login page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and enhancement
    const loginForm = document.querySelector('.login-form');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    // Add real-time validation
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            validateField(this);
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validateField(this);
        });
    }
    
    // Form submission enhancement
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Client-side validation
            let isValid = true;
            
            if (!usernameInput.value.trim()) {
                markInvalid(usernameInput, 'Username is required');
                isValid = false;
            } else {
                markValid(usernameInput);
            }
            
            if (!passwordInput.value) {
                markInvalid(passwordInput, 'Password is required');
                isValid = false;
            } else {
                markValid(passwordInput);
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill in all required fields correctly', 'error');
            } else {
                // Add loading state to button
                const submitBtn = loginForm.querySelector('.login-btn');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
                    submitBtn.disabled = true;
                }
            }
        });
    }
    
    // Demo credentials auto-fill for testing
    const demoCredentials = {
        'admin': 'password',
        'cashier1': 'password',
        'tailor1': 'password'
    };
    
    // Add demo buttons for easier testing
    addDemoButtons();
    
    function addDemoButtons() {
        const formGroups = document.querySelectorAll('.form-group');
        if (formGroups.length >= 2) {
            const demoContainer = document.createElement('div');
            demoContainer.className = 'demo-buttons mt-3';
            demoContainer.innerHTML = `
                <p class="text-center mb-2 small text-muted">Quick Fill (Demo):</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-primary demo-btn" data-username="admin">Admin</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary demo-btn" data-username="cashier1">Cashier</button>
                    <button type="button" class="btn btn-sm btn-outline-brown demo-btn" data-username="tailor1">Tailor</button>
                </div>
            `;
            
            formGroups[1].parentNode.insertBefore(demoContainer, formGroups[1].nextSibling);
            
            // Add event listeners to demo buttons
            document.querySelectorAll('.demo-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const username = this.getAttribute('data-username');
                    const password = demoCredentials[username];
                    
                    usernameInput.value = username;
                    passwordInput.value = password;
                    
                    // Trigger input events to update validation
                    usernameInput.dispatchEvent(new Event('input'));
                    passwordInput.dispatchEvent(new Event('input'));
                    
                    showToast(`Demo credentials for ${username} filled`, 'info');
                });
            });
        }
    }
    
    // Field validation functions
    function validateField(field) {
        if (!field.value.trim()) {
            markInvalid(field, 'This field is required');
            return false;
        } else {
            markValid(field);
            return true;
        }
    }
    
    function markInvalid(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        // Remove existing feedback
        let existingFeedback = field.parentNode.querySelector('.invalid-feedback');
        if (!existingFeedback) {
            existingFeedback = document.createElement('div');
            existingFeedback.className = 'invalid-feedback';
            field.parentNode.appendChild(existingFeedback);
        }
        existingFeedback.textContent = message;
    }
    
    function markValid(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        // Remove feedback if exists
        const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
    }
    
    // Toast notification function
    function showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.custom-toast');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${getToastIcon(type)} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Add show class after a brief delay
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
    
    function getToastIcon(type) {
        switch (type) {
            case 'error': return 'exclamation-circle';
            case 'success': return 'check-circle';
            case 'warning': return 'exclamation-triangle';
            default: return 'info-circle';
        }
    }
    
    // Add CSS for toast notifications
    const toastStyles = document.createElement('style');
    toastStyles.textContent = `
        .custom-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1050;
            max-width: 350px;
            border-left: 4px solid var(--primary);
        }
        
        .custom-toast.show {
            transform: translateX(0);
        }
        
        .toast-error {
            border-left-color: #dc3545;
        }
        
        .toast-success {
            border-left-color: #198754;
        }
        
        .toast-warning {
            border-left-color: #ffc107;
        }
        
        .toast-info {
            border-left-color: var(--secondary);
        }
        
        .toast-content {
            display: flex;
            align-items: center;
        }
        
        .btn-outline-brown {
            color: #6F4D38;
            border-color: #6F4D38;
        }
        
        .btn-outline-brown:hover {
            background-color: #6F4D38;
            color: white;
        }
    `;
    document.head.appendChild(toastStyles);
});