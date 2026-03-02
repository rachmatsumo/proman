<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProMan - Project Management')</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/proman-fav.png') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Alpine.js for some interactivity if needed -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('styles')
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
        }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
        }

        .sidebar { 
            min-width: var(--sidebar-width); 
            max-width: var(--sidebar-width); 
            transition: transform 0.3s ease;
            z-index: 1040;
        }
        
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

        /* Mobile specific styles */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background-color: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(4px);
                z-index: 1030;
            }
        }
        /* SweetAlert2 Layout Fix */
        html.swal2-shown, 
        body.swal2-shown,
        body {
            padding-right: 0 !important;
        }
        html.swal2-shown,
        body.swal2-shown {
            height: 100% !important;
            overflow: hidden !important;
        }
        .swal2-container {
            z-index: 9999 !important;
        }
    </style>
</head>
<body class="bg-light d-flex vh-100 overflow-hidden text-dark" x-data="{ sidebarOpen: false }">

    <!-- Sidebar Overlay (mobile only) -->
    <div class="sidebar-overlay d-lg-none" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" style="display: none;"></div>

    <!-- Sidebar -->
    <aside class="sidebar bg-dark-slate text-white d-flex flex-column h-100 shadow-lg border-end border-white border-opacity-10"
           :class="sidebarOpen ? 'show' : ''">
        <!-- Logo -->
        <div class="d-flex align-items-center justify-content-between px-4" style="height: 72px;">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-white rounded-3 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 36px; height: 36px;">
                     <img src="{{ asset('img/proman-logo.png') }}" alt="ProMan Logo" class="img-fluid">
                </div>
                <span class="fs-4 fw-bold tracking-tight">ProMan</span>
            </div>
            <button class="btn btn-link text-white p-0 d-lg-none" @click="sidebarOpen = false">
                <i class="fa-solid fa-xmark fs-4"></i>
            </button>
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
                    <span class="ms-2 fw-medium">Program</span>
                </a>

                <a href="{{ route('agendas.index') }}" class="nav-link text-slate-300 rounded-3 p-2 px-3 d-flex align-items-center transition-all {{ request()->routeIs('agendas.*') ? 'active shadow-sm' : '' }}">
                    <div class="nav-icon-wrapper d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-calendar-check fs-6"></i>
                    </div>
                    <span class="ms-2 fw-medium">Agenda</span>
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
    </aside>

    <!-- Main Content -->
    <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden">
        <!-- Top header -->
        <header class="py-4 bg-white shadow-sm d-flex align-items-center justify-content-between px-3 px-md-4 border-bottom" style="height: 64px; z-index: 10;">
            <div class="d-flex align-items-center gap-2 gap-md-3 overflow-hidden">
                <button class="btn btn-light border-0 d-lg-none shadow-sm rounded-3" @click="sidebarOpen = true">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
                <h1 class="fs-5 fw-semibold text-dark mb-0 text-truncate">
                    @yield('header_title', 'Dashboard')
                </h1>
            </div>
            <div class="d-flex align-items-center gap-2 gap-md-3">
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
    <script>
        // Global SweetAlert2 Mixins for Premium Experience
        const CustomSwal = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-primary px-4 py-2 fw-semibold rounded-pill mx-2 shadow-sm',
                cancelButton:  'btn btn-light px-4 py-2 fw-semibold rounded-pill mx-2 border shadow-sm',
                title:         'fw-bold text-dark fs-5',
                popup:         'rounded-4 border-0 shadow-lg',
                actions:       'gap-2',
            },
            buttonsStyling: false,
            scrollbarPadding: false,
            showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        });

        const ConfirmSwal = CustomSwal.mixin({
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                confirmButton: 'btn btn-danger px-4 py-2 fw-semibold rounded-pill mx-2 shadow-sm',
                cancelButton:  'btn btn-light px-4 py-2 fw-semibold rounded-pill mx-2 border shadow-sm',
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
