<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'Coffee Shop Attendance') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #D2B48C;
            --accent-color: #F4A460;
            --dark-color: #5D2A0A;
            --light-color: #F5F5DC;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .login-left {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>') repeat;
            background-size: 50px 50px;
        }

        .login-right {
            padding: 3rem;
        }

        .coffee-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .form-control {
            border: none;
            border-bottom: 2px solid #ddd;
            border-radius: 0;
            padding: 15px 0;
            background: transparent;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: none;
            border-bottom-color: var(--primary-color);
            background: transparent;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .alert {
            border: none;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .role-demo {
            background: rgba(139, 69, 19, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }

        .role-demo h6 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .demo-role {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .demo-role:hover {
            background: var(--dark-color);
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .login-left {
                padding: 2rem;
            }
            
            .login-right {
                padding: 2rem;
            }
            
            .coffee-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Side - Branding -->
                <div class="col-lg-5 login-left">
                    <div class="position-relative">
                        <div class="coffee-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Coffee Shop</h2>
                        <h4 class="mb-4">Attendance System</h4>
                        <p class="mb-4 opacity-90">
                            Flexible shift management with GPS tracking, role-based access control, 
                            and real-time reporting for your coffee shop chain.
                        </p>
                        
                        <!-- Demo Roles -->
                        <div class="role-demo">
                            <h6><i class="fas fa-users me-2"></i>Demo Roles Available:</h6>
                            <div class="demo-role" data-email="hr@coffee.com" data-password="password">HR Central</div>
                            <div class="demo-role" data-email="manager@coffee.com" data-password="password">Branch Manager</div>
                            <div class="demo-role" data-email="pengelola@coffee.com" data-password="password">Pengelola</div>
                            <div class="demo-role" data-email="employee@coffee.com" data-password="password">Employee</div>
                        </div>
                        
                        <div class="mt-4 small opacity-75">
                            <i class="fas fa-shield-alt me-2"></i>
                            Secure • Scalable • Flexible
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="col-lg-7 login-right">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold" style="color: var(--primary-color);">Welcome Back!</h3>
                        <p class="text-muted">Sign in to access your dashboard</p>
                    </div>
                    
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            @if($errors->has('email'))
                                {{ $errors->first('email') }}
                            @else
                                {{ $errors->first() }}
                            @endif
                        </div>
                    @endif
                    
                    @if (session('status'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf
                        
                        <div class="form-floating">
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   placeholder="name@example.com" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autocomplete="email" 
                                   autofocus>
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required 
                                   autocomplete="current-password">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-decoration-none" style="color: var(--primary-color);">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </div>
                    </form>
                    
                    <!-- Quick Login for Demo -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Click on any role badge above for quick demo login
                        </small>
                    </div>
                    
                    <!-- Features List -->
                    <div class="mt-4 pt-4 border-top">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="fas fa-mobile-alt mb-2" style="color: var(--primary-color);"></i>
                                <div class="small">Mobile Ready</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-map-marker-alt mb-2" style="color: var(--primary-color);"></i>
                                <div class="small">GPS Tracking</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-chart-bar mb-2" style="color: var(--primary-color);"></i>
                                <div class="small">Real-time Reports</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Demo role quick login
        document.querySelectorAll('.demo-role').forEach(role => {
            role.addEventListener('click', function() {
                const email = this.dataset.email;
                const password = this.dataset.password;
                
                document.getElementById('email').value = email;
                document.getElementById('password').value = password;
                
                // Highlight the form fields briefly
                document.getElementById('email').style.background = 'rgba(139, 69, 19, 0.1)';
                document.getElementById('password').style.background = 'rgba(139, 69, 19, 0.1)';
                
                setTimeout(() => {
                    document.getElementById('email').style.background = 'transparent';
                    document.getElementById('password').style.background = 'transparent';
                }, 1000);
            });
        });
        
        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = this.querySelector('.btn-login');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
