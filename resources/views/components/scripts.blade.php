<!-- Core JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- API Configuration -->
<script>
// Global API configuration
window.API = {
    baseURL: document.querySelector('meta[name="api-base-url"]').getAttribute('content'),
    token: localStorage.getItem('auth_token'),
    
    // Set auth token
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    },
    
    // Remove auth token
    removeToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    },
    
    // Make API request
    async request(endpoint, options = {}) {
        const url = this.baseURL + endpoint;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                ...options.headers
            },
            ...options
        };
        
        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                // Log full error details for debugging
                console.error('API Response Error:', {
                    status: response.status,
                    statusText: response.statusText,
                    data: data
                });
                
                // Handle validation errors specially
                if (response.status === 422 && data.errors) {
                    const error = new Error(data.message || 'Validation failed');
                    error.validationErrors = data.errors;
                    throw error;
                }
                
                throw new Error(data.message || `HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            
            // Handle authentication errors
            if (error.message && (error.message.includes('Unauthorized') || error.message.includes('Unauthenticated'))) {
                this.removeToken();
                if (window.location.pathname !== '/login') {
                    window.location.href = '/login';
                }
            }
            
            throw error;
        }
    },
    
    // GET request
    get(endpoint, params = {}) {
        let finalEndpoint = endpoint;
        const urlParams = new URLSearchParams();
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                urlParams.append(key, params[key]);
            }
        });
        if (urlParams.toString()) {
            finalEndpoint += '?' + urlParams.toString();
        }
        return this.request(finalEndpoint);
    },
    
    // POST request
    post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    // PUT request
    put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    // DELETE request
    delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    },
    
    // POST with FormData (for file uploads)
    postForm(endpoint, formData) {
        return this.request(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
    }
};

// Global utility functions
window.Utils = {
    // Show toast notification
    showToast(message, type = 'info', duration = 5000) {
        const toastContainer = this.getToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const toastHtml = `
            <div class="toast align-items-center border-0" role="alert" id="${toastId}">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="fas ${this.getToastIcon(type)} me-2 text-${type}"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: duration
        });
        
        // Auto remove from DOM after hiding
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
        
        toast.show();
    },
    
    // Get toast icon based on type
    getToastIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    },
    
    // Get or create toast container
    getToastContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    },
    
    // Format date
    formatDate(dateString, options = {}) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                console.error('Invalid date:', dateString);
                return dateString; // Return original string if invalid
            }
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                ...options
            });
        } catch (error) {
            console.error('Date formatting error:', error);
            return dateString;
        }
    },
    
    // Format time
    formatTime(timeString) {
        if (!timeString) return '';
        try {
            // Handle different time formats
            let timeToFormat = timeString;
            if (timeString.includes('T')) {
                timeToFormat = timeString.split('T')[1].split('.')[0];
            }
            if (!timeToFormat.includes(':')) {
                return timeString;
            }
            const [hours, minutes] = timeToFormat.split(':');
            return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
        } catch (error) {
            console.error('Time formatting error:', error);
            return timeString;
        }
    },
    
    // Format datetime
    formatDateTime(dateTimeString) {
        if (!dateTimeString) return '';
        try {
            const date = new Date(dateTimeString);
            if (isNaN(date.getTime())) {
                console.error('Invalid datetime:', dateTimeString);
                return dateTimeString;
            }
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            console.error('DateTime formatting error:', error);
            return dateTimeString;
        }
    },
    
    // Loading state for buttons
    setButtonLoading(button, loading = true) {
        if (loading) {
            if (!button.hasAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.innerHTML);
            }
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
        }
    },
    
    // Loading state for elements
    setElementLoading(element, loading = true) {
        if (loading) {
            element.classList.add('loading');
        } else {
            element.classList.remove('loading');
        }
    },
    
    // Confirm dialog
    async confirm(message, title = 'Confirmation') {
        return new Promise((resolve) => {
            const modalId = 'confirm-modal-' + Date.now();
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${message}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary confirm-btn">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            
            modalElement.querySelector('.confirm-btn').addEventListener('click', () => {
                modal.hide();
                resolve(true);
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                resolve(false);
            });
            
            modal.show();
        });
    },
    
    // Debounce function
    debounce(func, delay = 300) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    },
    
    // Handle API errors
    handleApiError(error, defaultMessage = 'An error occurred') {
        console.error('API Error:', error);
        
        let message = defaultMessage;
        if (error.message) {
            message = error.message;
        }
        
        this.showToast(message, 'error');
    }
};

// Initialize DataTables default settings
$.extend(true, $.fn.dataTable.defaults, {
    pageLength: 25,
    responsive: true,
    language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries per page",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        emptyTable: "No data available in table",
        paginate: {
            first: "First",
            last: "Last",
            next: "Next",
            previous: "Previous"
        }
    },
    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex align-items-center"f>>rtip',
    columnDefs: [
        { targets: '_all', className: 'align-middle' }
    ]
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);

// Close alerts when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('alert-dismissible')) {
        e.target.style.display = 'none';
    }
});

// Main content margin adjustment for sidebar
function adjustMainContent() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        mainContent.style.marginLeft = isCollapsed ? '0' : '280px';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize API token from PHP session
    @if(session('api_token'))
        API.setToken('{{ session('api_token') }}');
    @endif
    
    // Adjust main content layout
    adjustMainContent();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-focus first input in modals
    $(document).on('shown.bs.modal', '.modal', function () {
        $(this).find('input:text:visible:first').focus();
    });
});

// Global error handling
window.addEventListener('unhandledrejection', function(event) {
    if (event.reason && event.reason.message) {
        Utils.handleApiError(event.reason);
    }
});

// Prevent form double submission
$(document).on('submit', 'form', function() {
    const submitBtn = $(this).find('button[type="submit"]');
    if (submitBtn.length) {
        Utils.setButtonLoading(submitBtn[0], true);
        setTimeout(() => {
            Utils.setButtonLoading(submitBtn[0], false);
        }, 3000); // Reset after 3 seconds as fallback
    }
});
</script>

<style>
/* Toast styling */
.toast {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 10px;
}

.toast .toast-body {
    padding: 12px 16px;
}

.toast.bg-success,
.toast.bg-danger,
.toast.bg-warning,
.toast.bg-info {
    color: white;
}

/* Main content adjustment */
.main-content {
    margin-left: 280px;
    transition: margin-left 0.3s ease;
    padding: 30px;
    min-height: calc(100vh - 76px);
}

.main-content.sidebar-collapsed {
    margin-left: 0;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}
</style>
