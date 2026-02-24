<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ProMan - Project Management')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Alpine.js for some interactivity if needed -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @stack('styles')
    <style>
        .sidebar { min-width: 250px; max-width: 250px; }
        .bg-dark-slate { background-color: #0f172a; }
        .text-slate-300 { color: #cbd5e1; }
        .text-slate-400 { color: #94a3b8; }
        .nav-link.active { background-color: #4f46e5 !important; color: white !important; }
        .nav-link:hover:not(.active) { background-color: #1e293b; color: white; }
    </style>
</head>
<body class="bg-light d-flex vh-100 overflow-hidden text-dark" style="font-family: system-ui, -apple-system, sans-serif;">

    <!-- Sidebar -->
    <aside class="sidebar bg-dark-slate text-white d-flex flex-column h-100 shadow-lg z-3">
        <!-- Logo -->
        <div class="d-flex align-items-center px-4 border-bottom border-secondary" style="height: 64px;">
            <i class="fa-solid fa-layer-group text-primary fs-4 me-3" style="color: #818cf8 !important;"></i>
            <span class="fs-5 fw-bold tracking-wide">ProMan</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-grow-1 overflow-auto py-3 px-2">
            <div class="d-flex flex-column gap-1">
                <a href="{{ route('projects.index') }}" class="nav-link text-slate-300 rounded p-3 d-flex align-items-center transition-colors {{ request()->routeIs('projects.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie text-center fs-5" style="width: 24px;"></i>
                    <span class="ms-3 fw-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('programs.index') }}" class="nav-link text-slate-300 rounded p-3 d-flex align-items-center transition-colors {{ request()->routeIs('programs.index') || request()->routeIs('programs.show') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check text-center fs-5" style="width: 24px;"></i>
                    <span class="ms-3 fw-medium">List Program</span>
                </a>
                
                <!-- <a href="{{ route('projects.gantt') }}" class="nav-link text-slate-300 rounded p-3 d-flex align-items-center transition-colors {{ request()->routeIs('projects.gantt') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-gantt text-center fs-5" style="width: 24px;"></i>
                    <span class="ms-3 fw-medium">Timeline Chart</span>
                </a>
                
                <a href="{{ route('projects.calendar') }}" class="nav-link text-slate-300 rounded p-3 d-flex align-items-center transition-colors {{ request()->routeIs('projects.calendar') ? 'active' : '' }}">
                    <i class="fa-regular fa-calendar-days text-center fs-5" style="width: 24px;"></i>
                    <span class="ms-3 fw-medium">Calendar Activity</span>
                </a> -->
            </div>
            
            <hr class="border-secondary my-4">

            <!-- Data Input Links -->
            <!-- <div class="px-3 pb-2 small fw-semibold text-slate-400 text-uppercase" style="letter-spacing: 0.05em;">
                Management
            </div>
            <div class="d-flex flex-column gap-1">
                <a href="{{ route('programs.create') }}" class="nav-link text-slate-300 rounded py-2 px-3 d-flex align-items-center transition-colors">
                    <i class="fa-solid fa-folder-plus text-center" style="width: 24px;"></i>
                    <span class="ms-3 small">Add Program</span>
                </a>
                <a href="{{ route('sub_programs.create') }}" class="nav-link text-slate-300 rounded py-2 px-3 d-flex align-items-center transition-colors">
                    <i class="fa-solid fa-folder-tree text-center" style="width: 24px;"></i>
                    <span class="ms-3 small">Add Sub Program</span>
                </a>
                <a href="{{ route('milestones.create') }}" class="nav-link text-slate-300 rounded py-2 px-3 d-flex align-items-center transition-colors">
                    <i class="fa-solid fa-flag-checkered text-center" style="width: 24px;"></i>
                    <span class="ms-3 small">Add Milestone</span>
                </a>
                <a href="{{ route('activities.create') }}" class="nav-link text-slate-300 rounded py-2 px-3 d-flex align-items-center transition-colors">
                    <i class="fa-solid fa-clipboard-list text-center" style="width: 24px;"></i>
                    <span class="ms-3 small">Add Activity</span>
                </a>
            </div> -->
        </nav>
        
        <!-- Bottom section -->
        <div class="border-top border-secondary p-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 14px;">
                    AD
                </div>
                <div class="ms-3">
                    <p class="mb-0 small fw-medium">Admin User</p>
                </div>
            </div>
        </div>
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
