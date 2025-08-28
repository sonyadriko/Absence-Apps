<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Coffee Shop Attendance') }} - Professional Attendance Management</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #D2B48C;
            --accent-color: #F4A460;
            --dark-color: #5D2A0A;
            --light-color: #F5F5DC;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Figtree', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)" /></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .coffee-icon {
            font-size: 6rem;
            opacity: 0.1;
            position: absolute;
            top: 20%;
            right: 10%;
            animation: float 6s ease-in-out infinite;
        }

        .coffee-icon:nth-child(2) {
            top: 60%;
            right: 20%;
            font-size: 4rem;
            animation-delay: -2s;
        }

        .coffee-icon:nth-child(3) {
            top: 40%;
            right: 5%;
            font-size: 5rem;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .brand-logo {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, white, var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
        }

        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .btn-outline-custom:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,255,255,0.2);
        }

        .features-section {
            padding: 80px 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .stats-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 80px 0;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            color: var(--primary-color);
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }

        .nav-top {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .nav-top .btn {
            margin-left: 10px;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .brand-logo {
                font-size: 2.5rem;
            }
            .hero-section {
                text-align: center;
            }
            .coffee-icon {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Navigation -->
    @if (Route::has('login'))
        <div class="nav-top">
            @auth
                <a href="{{ url('/home') }}" class="btn btn-light">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-light">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                @endif
            @endauth
        </div>
    @endif

    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <i class="fas fa-coffee floating-element" style="top: 10%; left: 5%; font-size: 2rem; animation-delay: 0s;"></i>
        <i class="fas fa-clock floating-element" style="top: 20%; left: 80%; font-size: 1.5rem; animation-delay: -1s;"></i>
        <i class="fas fa-users floating-element" style="top: 50%; left: 10%; font-size: 1.8rem; animation-delay: -2s;"></i>
        <i class="fas fa-calendar floating-element" style="top: 70%; left: 85%; font-size: 1.6rem; animation-delay: -3s;"></i>
        <i class="fas fa-chart-bar floating-element" style="top: 30%; left: 90%; font-size: 1.4rem; animation-delay: -4s;"></i>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <i class="fas fa-coffee coffee-icon"></i>
        <i class="fas fa-coffee coffee-icon"></i>
        <i class="fas fa-coffee coffee-icon"></i>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="brand-logo mb-4">
                        <i class="fas fa-coffee me-3"></i>
                        {{ config('app.name', 'Coffee Shop Attendance') }}
                    </div>
                    <h1 class="display-4 fw-bold mb-4">
                        Professional Attendance Management for Modern Coffee Shops
                    </h1>
                    <p class="lead mb-5 opacity-90">
                        Streamline your workforce management with our comprehensive attendance tracking system. 
                        Built specifically for multi-outlet coffee shop operations with flexible RBAC, 
                        real-time reporting, and intelligent scheduling.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/home') }}" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Get Started
                            </a>
                            <a href="#features" class="btn btn-outline-custom btn-lg">
                                <i class="fas fa-info-circle me-2"></i>
                                Learn More
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="position-relative">
                        <div style="font-size: 15rem; opacity: 0.1;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="bg-white bg-opacity-90 rounded-3 p-3 shadow">
                                        <i class="fas fa-fingerprint text-primary fs-2"></i>
                                        <div class="small fw-bold text-dark mt-1">Check In/Out</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-white bg-opacity-90 rounded-3 p-3 shadow">
                                        <i class="fas fa-users text-success fs-2"></i>
                                        <div class="small fw-bold text-dark mt-1">Team Management</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-white bg-opacity-90 rounded-3 p-3 shadow">
                                        <i class="fas fa-chart-line text-info fs-2"></i>
                                        <div class="small fw-bold text-dark mt-1">Real-time Reports</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-white bg-opacity-90 rounded-3 p-3 shadow">
                                        <i class="fas fa-mobile-alt text-warning fs-2"></i>
                                        <div class="small fw-bold text-dark mt-1">Mobile Ready</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold text-dark mb-4">
                        Everything You Need for Workforce Management
                    </h2>
                    <p class="lead text-muted">
                        Comprehensive features designed specifically for coffee shop operations
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Smart Check-in System</h4>
                        <p class="text-muted">
                            GPS geofencing, selfie verification, and QR code support ensure accurate attendance tracking
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Flexible RBAC System</h4>
                        <p class="text-muted">
                            49 granular permissions with role-based access control. Create custom roles without coding
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Multi-Outlet Management</h4>
                        <p class="text-muted">
                            Manage multiple branches with centralized control and branch-specific permissions
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Intelligent Scheduling</h4>
                        <p class="text-muted">
                            Flexible shift patterns, peak hour tracking, and automated schedule optimization
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Advanced Analytics</h4>
                        <p class="text-muted">
                            Real-time dashboards, attendance trends, and comprehensive reporting tools
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Mobile Optimized</h4>
                        <p class="text-muted">
                            Responsive design works perfectly on all devices. Check in from anywhere, anytime
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number">49+</span>
                        <div class="stat-label">Permissions Available</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number">8</span>
                        <div class="stat-label">Pre-built Roles</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number">100%</span>
                        <div class="stat-label">Mobile Responsive</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number">24/7</span>
                        <div class="stat-label">Real-time Tracking</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-coffee me-2 fs-4"></i>
                        <span class="fw-bold">{{ config('app.name', 'Coffee Shop Attendance') }}</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Built with Laravel {{ Illuminate\Foundation\Application::VERSION }} & PHP {{ PHP_VERSION }}
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Smooth scrolling for anchor links -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
