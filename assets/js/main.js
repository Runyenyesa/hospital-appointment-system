/**
 * Main JavaScript for Hospital Appointment Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar Toggle
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const mobileOverlay = document.getElementById('mobileOverlay');
    
    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                if (mobileOverlay) mobileOverlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });
    }
    
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            mobileOverlay.classList.remove('show');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic time display
    function updateTime() {
        const timeElements = document.querySelectorAll('.live-time');
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        timeElements.forEach(function(el) {
            el.textContent = timeString;
        });
    }
    
    if (document.querySelectorAll('.live-time').length > 0) {
        updateTime();
        setInterval(updateTime, 60000);
    }
    
    // Form validation enhancement
    document.querySelectorAll('form[data-validate]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                    
                    // Add error message if not exists
                    let feedback = field.nextElementSibling;
                    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        field.parentNode.insertBefore(feedback, field.nextSibling);
                    }
                    feedback.textContent = 'This field is required';
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(function(field) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailPattern.test(field.value)) {
                    valid = false;
                    field.classList.add('is-invalid');
                }
            });
            
            // Phone validation
            const phoneFields = form.querySelectorAll('input[type="tel"]');
            phoneFields.forEach(function(field) {
                const phonePattern = /^[\d\s\-\+\(\)]{10,}$/;
                if (field.value && !phonePattern.test(field.value)) {
                    valid = false;
                    field.classList.add('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                // Focus first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    });
    
    // Clear validation on input
    document.querySelectorAll('input, select, textarea').forEach(function(field) {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // AJAX form submissions
    document.querySelectorAll('form[data-ajax]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const url = form.getAttribute('action') || window.location.href;
            const method = form.getAttribute('method') || 'POST';
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
            }
            
            fetch(url, {
                method: method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.message) {
                        showToast(data.message, 'success');
                    }
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 500);
                    }
                    if (data.reload) {
                        setTimeout(() => window.location.reload(), 500);
                    }
                } else {
                    showToast(data.error || 'An error occurred', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        });
    });
    
    // Toast notification helper
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    // Date input min = today
    document.querySelectorAll('input[type="date"][data-min-today]').forEach(function(input) {
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('min', today);
    });
    
    // Dynamic modal content loading
    document.querySelectorAll('[data-bs-toggle="modal"][data-load-url]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = document.querySelector(this.getAttribute('data-bs-target'));
            const url = this.getAttribute('data-load-url');
            const target = modal.querySelector(this.getAttribute('data-load-target') || '.modal-body');
            
            if (url && target) {
                target.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        target.innerHTML = html;
                    })
                    .catch(() => {
                        target.innerHTML = '<div class="alert alert-danger">Failed to load content</div>';
                    });
            }
        });
    });
    
});

/**
 * Confirm action helper
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format date helper
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Format time helper
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(hours, minutes);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
