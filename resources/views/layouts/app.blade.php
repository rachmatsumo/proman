<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ProMan - Project Management')</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/proman-fav.png') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Alpine.js for some interactivity if needed -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @stack('styles')
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
        }
        .sidebar { min-width: var(--sidebar-width); max-width: var(--sidebar-width); transition: all 0.3s ease; }
        .bg-dark-slate { background-color: var(--slate-900); }
        .text-slate-300 { color: #cbd5e1; }
        .text-slate-400 { color: #94a3b8; }
        
        #sidebar .nav-link {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        #sidebar .nav-link.active { 
            background-color: rgba(255, 255, 255, 0.1) !important; 
            color: white !important;
            border-color: rgba(255, 255, 255, 0.1);
        }
        #sidebar .nav-link.active .nav-icon-wrapper {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        #sidebar .nav-link:hover:not(.active) { 
            background-color: rgba(255, 255, 255, 0.05); 
            color: white; 
            transform: translateX(4px);
        }
        #sidebar .nav-link:hover:not(.active) .nav-icon-wrapper {
            color: var(--primary-color);
        }
        .transition-all { transition: all 0.3s ease; }
        .hover-text-white:hover { color: white !important; }
        
        /* Custom scrollbar for sidebar */
        .sidebar nav::-webkit-scrollbar { width: 4px; }
        .sidebar nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
    </style>
</head>
<body class="bg-light d-flex vh-100 overflow-hidden text-dark" style="font-family: system-ui, -apple-system, sans-serif;">

    <!-- Sidebar -->
    <aside class="sidebar bg-dark-slate text-white d-flex flex-column h-100 shadow-lg z-3 border-end border-white border-opacity-10">
        <!-- Logo -->
        <div class="d-flex align-items-center px-4" style="height: 72px;">
            <div class="bg-primary bg-white rounded-3 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 36px; height: 36px;">
                <!-- <i class="fa-solid fa-layer-group text-white fs-5"></i> -->
                 <img src="{{ asset('img/proman-logo.png') }}" alt="ProMan Logo" class="img-fluid">
            </div>
            <span class="fs-4 fw-bold tracking-tight">ProMan</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-grow-1 overflow-auto py-4 px-3" id="sidebar">
            <div class="d-flex flex-column gap-2">
                <div class="px-3 mb-2 small fw-bold text-slate-400 text-uppercase opacity-50" style="letter-spacing: 0.1em; font-size: 0.65rem;">
                    Main Menu
                </div>
                
                <a href="{{ route('projects.index') }}" class="nav-link text-slate-300 rounded-3 p-2 px-3 d-flex align-items-center transition-all {{ request()->routeIs('projects.index') ? 'active shadow-sm' : '' }}">
                    <div class="nav-icon-wrapper d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-house-chimney fs-6"></i>
                    </div>
                    <span class="ms-2 fw-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('programs.index') }}" class="nav-link text-slate-300 rounded-3 p-2 px-3 d-flex align-items-center transition-all {{ request()->routeIs('programs.index') || request()->routeIs('programs.show') ? 'active shadow-sm' : '' }}">
                    <div class="nav-icon-wrapper d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-briefcase fs-6"></i>
                    </div>
                    <span class="ms-2 fw-medium">List Program</span>
                </a>
            </div>
            
            <div class="mt-5 px-3 mb-2 small fw-bold text-slate-400 text-uppercase opacity-50" style="letter-spacing: 0.1em; font-size: 0.65rem;">
                Resources
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('users.index') }}" class="nav-link text-slate-300 rounded-3 p-2 px-3 d-flex align-items-center transition-all {{ request()->routeIs('users.*') ? 'active shadow-sm' : '' }}">
                    <div class="nav-icon-wrapper d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-users fs-6"></i>
                    </div>
                    <span class="ms-2 fw-medium">Team Members</span>
                </a>
                <a href="#" class="nav-link text-slate-300 rounded-3 p-2 px-3 d-flex align-items-center transition-all opacity-75">
                    <div class="nav-icon-wrapper d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-gear fs-6"></i>
                    </div>
                    <span class="ms-2 fw-medium">Settings</span>
                </a>
            </div>
        </nav>
        
        <!-- Bottom section -->
        <!-- <div class="p-3 mt-auto">
            <div class="user-profile bg-white bg-opacity-10 border border-white border-opacity-10 rounded-4 p-3 d-flex align-items-center">
                <div class="avatar-wrapper position-relative">
                    <div class="rounded-circle bg-primary bg-gradient d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 40px; height: 40px; font-size: 14px;">
                        AR
                    </div>
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-slate-900 rounded-circle" style="width: 12px; height: 12px;"></span>
                </div>
                <div class="ms-3 overflow-hidden">
                    <p class="mb-0 small fw-bold text-white text-truncate">Admin User</p>
                    <p class="mb-0 text-slate-400 text-truncate" style="font-size: 0.7rem;">Project Lead</p>
                </div>
                <button class="ms-auto btn btn-link text-slate-400 p-0 hover-text-white">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </div>
        </div> -->
    </aside>

    <!-- Main Content -->
    <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden">
        <!-- Top header -->
        <header class="py-4 bg-white shadow-sm d-flex align-items-center justify-content-between px-4 border-bottom" style="height: 64px; z-index: 10;">
            <h1 class="fs-5 fw-semibold text-dark mb-0">
                @yield('header_title', 'Dashboard')
            </h1>
            <div class="d-flex align-items-center gap-3">
                @yield('header_actions')
                
                @auth
                <div class="dropdown">
                    <button class="btn btn-light border-0 d-flex align-items-center gap-2 px-2 rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset(Auth::user()->avatar) }}" class="rounded-circle shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="small fw-semibold text-dark d-none d-md-inline">{{ Auth::user()->name }}</span>
                        <i class="fa-solid fa-chevron-down small text-muted ms-1" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2 mt-2" style="min-width: 200px;">
                        <li>
                            <div class="px-3 py-2 border-bottom mb-2 pb-2">
                                <div class="fw-bold text-dark small">{{ Auth::user()->name }}</div>
                                <div class="text-muted small" style="font-size: 0.7rem;">{{ Auth::user()->email }}</div>
                            </div>
                        </li>
                        <li><a class="dropdown-item rounded-3 small py-2" href="#"><i class="fa-regular fa-user me-2 opacity-50"></i> Profile Setting</a></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 small py-2 text-danger">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2 opacity-50"></i> Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @endauth
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow-1 overflow-auto bg-light d-flex flex-column p-0">
            <div class="p-4 flex-grow-1">
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger shadow-sm d-flex align-items-start" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5 me-3 mt-1"></i>
                        <div>
                            <p class="fw-medium mb-1">Please fix the following errors:</p>
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
            
            <!-- Global Footer -->
            <footer class="mt-auto py-3 text-center text-muted" style="font-size: 0.8rem; border-top: 1px dashed #e2e8f0;">
                <div class="container-fluid">
                    <span class="fw-medium">ProMan &copy; {{ date('Y') }}</span>
                    <span class="mx-2">&bull;</span>
                    <span>Project Management System</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
