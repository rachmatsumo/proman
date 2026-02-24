<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ProMan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
        }
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem 0;
        }
        .auth-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 100% 0%, rgba(79, 70, 229, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 0% 100%, rgba(124, 58, 237, 0.05) 0%, transparent 50%);
            z-index: -1;
        }
        .auth-card {
            width: 100%;
            max-width: 480px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .brand-logo {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
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
    </style>
</head>
<body>
    <div class="auth-background"></div>
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="brand-logo">
                <i class="fa-solid fa-user-plus text-white fs-5"></i>
            </div>
            <h2 class="fw-bold text-dark">Join ProMan</h2>
            <p class="text-muted small">Start managing your projects efficiently today</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-regular fa-user text-muted"></i></span>
                    <input type="text" name="name" class="form-control border-start-0" placeholder="John Doe" value="{{ old('name') }}" required autofocus style="border-radius: 0 0.75rem 0.75rem 0;">
                </div>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-regular fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="name@company.com" value="{{ old('email') }}" required style="border-radius: 0 0.75rem 0.75rem 0;">
                </div>
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="At least 8 characters" required style="border-radius: 0 0.75rem 0.75rem 0;">
                </div>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 0.75rem 0 0 0.75rem;"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password_confirmation" class="form-control border-start-0" placeholder="••••••••" required style="border-radius: 0 0.75rem 0.75rem 0;">
                </div>
            </div>

            <button type="submit" class="btn btn-auth">Create Account</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small mb-0">Already have an account? <a href="{{ route('login') }}" class="auth-link">Sign In</a></p>
        </div>
    </div>
</body>
</html>
