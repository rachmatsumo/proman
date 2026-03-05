<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ProMan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }
        body {
            background: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow: hidden;
        }
        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }
        .login-visual {
            flex: 1.2;
            position: relative;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 4rem;
            color: white;
            overflow: hidden;
        }
        .login-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("{{ asset('img/login-bg.png') }}") center center / cover no-repeat;
            opacity: 0.6;
            z-index: 1;
        }
        .login-visual::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.2) 60%, transparent 100%);
            z-index: 2;
        }
        .visual-content {
            position: relative;
            z-index: 3;
            max-width: 600px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-form-side {
            flex: 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            z-index: 4;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .brand-logo {
            width: 52px;
            height: 52px;
            background: var(--primary-gradient);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
        .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: all 0.2s;
        }
        .form-control:focus {
            background: #fff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-auth {
            background: var(--primary-gradient);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            opacity: 0.95;
        }
        .auth-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        .auth-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 991px) {
            .login-visual {
                display: none;
            }
            body {
                background: #f8fafc;
            }
            .auth-card {
                background: white;
                border-radius: 2rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
                padding: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Visual Side -->
        <div class="login-visual">
            <div class="visual-content">
                <div class="mb-4">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3" style="background: var(--primary-gradient) !important;">New Experience</span>
                    <h1 class="display-4 fw-bold mb-3">Manage Better with ProMan</h1>
                    <p class="fs-5 opacity-75 mb-0">Solusi manajemen proyek terintegrasi untuk tim Anda. Kelola program, proyek, dan agenda dengan jauh lebih mudah.</p>
                </div>
            </div>
        </div>

        <!-- Form Side -->
        <div class="login-form-side">
            <div class="auth-card">
                <div class="mb-4">
                    <div class="brand-logo">
                         <img src="{{ asset('img/proman-logo.png') }}" alt="Logo" class="img-fluid p-2">
                    </div>
                    <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
                    <p class="text-muted small">Enter your credentials to access your workspace</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="name@company.com" value="{{ old('email') }}" required autofocus style="border-radius: 0 0.75rem 0.75rem 0;">
                        </div>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required style="border-radius: 0 0.75rem 0.75rem 0;">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label small text-muted" for="remember">Remember me</label>
                        </div>
                        <!-- <a href="#" class="small auth-link">Forgot password?</a> -->
                    </div>

                    <button type="submit" class="btn btn-auth">Sign In</button>
                </form>
 
            </div>
        </div>
    </div>
</body>
</html>
