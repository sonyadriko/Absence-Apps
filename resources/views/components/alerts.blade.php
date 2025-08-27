@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2 text-success"></i>
            <div>
                <strong>Success!</strong> {{ session('success') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2 text-danger"></i>
            <div>
                <strong>Error!</strong> {{ session('error') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            <div>
                <strong>Warning!</strong> {{ session('warning') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2 text-info"></i>
            <div>
                <strong>Info!</strong> {{ session('info') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-circle me-2 mt-1 text-danger flex-shrink-0"></i>
            <div>
                <strong>Validation Errors:</strong>
                <ul class="list-unstyled mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li class="mb-1">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<style>
.alert {
    border-radius: 8px;
    border: 1px solid;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    padding: 16px 20px;
}

.alert-success {
    background-color: #f8f9fa;
    border-color: #28a745;
    color: #1e7e34;
}

.alert-danger {
    background-color: #f8f9fa;
    border-color: #dc3545;
    color: #721c24;
}

.alert-warning {
    background-color: #f8f9fa;
    border-color: #ffc107;
    color: #856404;
}

.alert-info {
    background-color: #f8f9fa;
    border-color: #17a2b8;
    color: #0c5460;
}

.alert .btn-close {
    position: absolute;
    top: 12px;
    right: 16px;
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.alert .btn-close:hover {
    opacity: 1;
}

.alert i {
    font-size: 1.1em;
}

.alert strong {
    font-weight: 600;
}

.alert ul {
    margin-top: 8px;
    margin-bottom: 0;
}

.alert ul li {
    padding-left: 0;
    position: relative;
}

.alert ul li:before {
    content: "•";
    margin-right: 8px;
    font-weight: bold;
}

/* Animation for alert appearance */
@keyframes slideInDown {
    from {
        transform: translate3d(0, -100%, 0);
        visibility: visible;
    }
    to {
        transform: translate3d(0, 0, 0);
    }
}

.alert.fade.show {
    animation: slideInDown 0.3s ease-out;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .alert {
        padding: 12px 16px;
        margin-left: -15px;
        margin-right: -15px;
        border-radius: 0;
    }
    
    .alert .btn-close {
        top: 8px;
        right: 12px;
    }
}
</style>
