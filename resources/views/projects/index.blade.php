@extends('layouts.app')

@section('title', 'Dashboard - ProMan')
@section('header_title', 'Dashboard')

@section('content')
<div class="container-fluid py-2 d-flex flex-column gap-4">

    {{-- ===== TOP SECTION: Hero Banner + Stats ===== --}}
    <div class="row g-3">

        {{-- Welcome Banner --}}
        <div class="col-12 col-lg-5">
            <div class="rounded-3 shadow-sm p-4 h-100 d-flex align-items-center overflow-hidden position-relative"
                 style="background: linear-gradient(135deg, #4f46e5, #7c3aed); min-height: 140px;">
                {{-- Background decoration --}}
                <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 9rem; line-height: 1; right: -1rem; top: -1rem;">
                    <i class="fa-solid fa-chart-pie text-white opacity-50"></i>
                </div>
                <div class="position-relative z-1 w-100">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="min-width: 42px; min-height: 42px;">
                            <i class="fa-solid fa-layer-group text-white fs-5"></i>
                        </div>
                        <div>
                            <h2 class="fs-5 fw-bold text-white mb-0">Welcome to Project Management System</h2>
                            <p class="text-white opacity-75 mb-0" style="font-size: 0.78rem;">Your project portfolio at a glance.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('programs.create') }}" class="btn btn-light btn-sm fw-semibold text-primary shadow-sm">
                            <i class="fa-solid fa-plus me-1"></i> New Program
                        </a>
                        <a href="{{ route('programs.index') }}" class="btn btn-sm fw-semibold text-white border border-white border-opacity-50" style="background: rgba(255,255,255,0.1);">
                            <i class="fa-solid fa-list me-1"></i> View All
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="col-12 col-lg-7">
            <div class="row g-3 h-100">
                {{-- Total Programs --}}
                <div class="col-6">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden position-relative" style="border-left: 4px solid #4f46e5 !important;">
                        <div class="card-body d-flex align-items-center p-3 ps-4">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.08em;">Total Programs</p>
                                <p class="display-6 fw-black text-dark mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">{{ $totalPrograms }}</p>
                            </div>
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #eef2ff;">
                                <i class="fa-solid fa-folder-open fs-3" style="color: #4f46e5;"></i>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-0 end-0" style="height: 3px; background: linear-gradient(to right, #4f46e5, #7c3aed);"></div>
                    </div>
                </div>
                {{-- Total Activities --}}
                <div class="col-6">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden position-relative" style="border-left: 4px solid #059669 !important;">
                        <div class="card-body d-flex align-items-center p-3 ps-4">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.08em;">Total Activities</p>
                                <p class="display-6 fw-black text-dark mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">{{ $totalActivities }}</p>
                            </div>
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #d1fae5;">
                                <i class="fa-solid fa-clipboard-list fs-3" style="color: #059669;"></i>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-0 end-0" style="height: 3px; background: linear-gradient(to right, #059669, #10b981);"></div>
                    </div>
                </div>
                {{-- System: Delayed --}}
                <div class="col-6">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden position-relative" style="border-left: 4px solid #dc2626 !important;">
                        <div class="card-body d-flex align-items-center p-3 ps-4">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.08em;">Delayed</p>
                                <p class="display-6 fw-black text-dark mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">{{ $systemStatusCounts['Delayed'] }}</p>
                            </div>
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #fef2f2;">
                                <i class="fa-solid fa-triangle-exclamation fs-3" style="color: #dc2626;"></i>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-0 end-0" style="height: 3px; background: linear-gradient(to right, #dc2626, #f97316);"></div>
                    </div>
                </div>
                {{-- System: Active --}}
                <div class="col-6">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden position-relative" style="border-left: 4px solid #0284c7 !important;">
                        <div class="card-body d-flex align-items-center p-3 ps-4">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.08em;">Active Now</p>
                                <p class="display-6 fw-black text-dark mb-0" style="font-size: 2.2rem; letter-spacing: -1px;">{{ $systemStatusCounts['Active'] }}</p>
                            </div>
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #e0f2fe;">
                                <i class="fa-solid fa-bolt fs-3" style="color: #0284c7;"></i>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-0 end-0" style="height: 3px; background: linear-gradient(to right, #0284c7, #7c3aed);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MIDDLE SECTION: Dual Status ===== --}}
    <div class="row g-4">

        {{-- System Status --}}
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom px-4 py-3 d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #eef2ff;">
                        <i class="fa-solid fa-robot" style="color: #4f46e5; font-size: 0.85rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="fw-bold fs-6 mb-0 text-dark">System Status</h3>
                        <p class="text-muted mb-0" style="font-size: 0.7rem;">Dihitung otomatis berdasarkan progress & timeline.</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php
                            $sysBars = [
                                'Upcoming'  => ['count' => $systemStatusCounts['Upcoming'],  'color' => '#94a3b8', 'bg' => '#f1f5f9', 'icon' => 'fa-clock'],
                                'Active'    => ['count' => $systemStatusCounts['Active'],    'color' => '#4f46e5', 'bg' => '#eef2ff', 'icon' => 'fa-bolt'],
                                'Delayed'   => ['count' => $systemStatusCounts['Delayed'],   'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'fa-triangle-exclamation'],
                                'Completed' => ['count' => $systemStatusCounts['Completed'], 'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-circle-check'],
                            ];
                            $totalSys = max(1, array_sum(array_column($sysBars, 'count')));
                        @endphp
                        @foreach($sysBars as $label => $cfg)
                        <div class="col-6">
                            <div class="rounded-3 p-3" style="background: {{ $cfg['bg'] }}; border: 1px solid {{ $cfg['color'] }}22;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-semibold" style="font-size: 0.72rem; color: {{ $cfg['color'] }}; text-transform: uppercase; letter-spacing: 0.06em;">{{ $label }}</span>
                                    <i class="fa-solid {{ $cfg['icon'] }}" style="color: {{ $cfg['color'] }}; opacity: 0.7; font-size: 0.8rem;"></i>
                                </div>
                                <p class="fw-black mb-1" style="font-size: 1.9rem; color: {{ $cfg['color'] }}; letter-spacing: -1px; line-height: 1;">{{ $cfg['count'] }}</p>
                                <div class="progress mt-2" style="height: 4px; background: rgba(0,0,0,0.08);">
                                    <div class="progress-bar" style="width: {{ round($cfg['count'] / $totalSys * 100) }}%; background-color: {{ $cfg['color'] }};"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Manual Status --}}
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom px-4 py-3 d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #ecfeff;">
                        <i class="fa-solid fa-user-pen" style="color: #0891b2; font-size: 0.85rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="fw-bold fs-6 mb-0 text-dark">Manual Status Summary</h3>
                        <p class="text-muted mb-0" style="font-size: 0.7rem;">Input status manual pada level activity.</p>
                    </div>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    @php
                        $manualStatuses = [
                            'Draft'       => ['count' => $statusCounts['Draft'],       'color' => '#64748b', 'bg' => '#f1f5f9'],
                            'To Do'       => ['count' => $statusCounts['To Do'],       'color' => '#0891b2', 'bg' => '#ecfeff'],
                            'On Progress' => ['count' => $statusCounts['On Progress'], 'color' => '#d97706', 'bg' => '#fffbeb'],
                            'On Hold'     => ['count' => $statusCounts['On Hold'],     'color' => '#f97316', 'bg' => '#fff7ed'],
                            'Done'        => ['count' => $statusCounts['Done'],        'color' => '#059669', 'bg' => '#d1fae5'],
                            'Cancelled'   => ['count' => $statusCounts['Cancelled'],   'color' => '#dc2626', 'bg' => '#fef2f2'],
                        ];
                        $totalManual = max(1, array_sum(array_column($manualStatuses, 'count')));
                    @endphp
                    <div class="d-flex flex-column gap-2">
                        @foreach($manualStatuses as $label => $cfg)
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end fw-bold" style="width: 22px; color: {{ $cfg['color'] }}; font-size: 0.9rem;">{{ $cfg['count'] }}</div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-muted" style="font-size: 0.73rem;">{{ $label }}</span>
                                    <span style="font-size: 0.65rem; color: {{ $cfg['color'] }};">{{ $totalManual > 0 ? round($cfg['count'] / $totalManual * 100) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 6px; background: {{ $cfg['bg'] }}; border: 1px solid {{ $cfg['color'] }}22;">
                                    <div class="progress-bar" style="width: {{ $totalManual > 0 ? round($cfg['count'] / $totalManual * 100) : 0 }}%; background-color: {{ $cfg['color'] }};"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== BOTTOM: Focus Watchlist ===== --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #fff7ed;">
                    <i class="fa-solid fa-crosshairs" style="color: #f97316; font-size: 0.85rem;"></i>
                </div>
                <div>
                    <h3 class="fw-bold fs-6 mb-0 text-dark">Focus Watchlist</h3>
                    <p class="text-muted mb-0" style="font-size: 0.7rem;">Top 5 activities that still need attention.</p>
                </div>
            </div>
            <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; font-size: 0.7rem;">
                <i class="fa-solid fa-eye me-1"></i> Top 5 Uncompleted
            </span>
        </div>
        <div class="card-body p-0">
            @if($recentActivities->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                        <thead>
                            <tr style="background: #f8fafc; font-size: 0.68rem; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8;">
                                <th class="px-4 py-3 fw-semibold border-bottom">Activity</th>
                                <th class="px-3 py-3 fw-semibold border-bottom">Path</th>
                                <th class="px-3 py-3 fw-semibold border-bottom text-center" style="width: 130px;">Progress</th>
                                <th class="px-3 py-3 fw-semibold border-bottom text-center" style="width: 130px;">Status</th>
                                <th class="px-3 py-3 fw-semibold border-bottom text-end" style="width: 120px;">Timeline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivities as $act)
                            @php
                                $manualColor = '#64748b';
                                if($act->status == 'To Do')       $manualColor = '#0891b2';
                                if($act->status == 'On Progress')  $manualColor = '#d97706';
                                if($act->status == 'On Hold')      $manualColor = '#f97316';
                                if($act->status == 'Done')         $manualColor = '#059669';
                                if($act->status == 'Cancelled')    $manualColor = '#dc2626';

                                $sysColor = '#64748b';
                                $sys = $act->system_status;
                                if($sys == 'Active')    $sysColor = '#4f46e5';
                                if($sys == 'Delayed')   $sysColor = '#dc2626';
                                if($sys == 'Completed') $sysColor = '#059669';
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold text-dark mb-1">{{ $act->name }}</div>
                                    @if($act->uic || $act->pic)
                                    <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-2" style="background: #f1f5f9; font-size: 0.65rem; color: #475569;">
                                        @if($act->uic)<span><i class="fa-solid fa-building me-1 opacity-50"></i>{{ $act->uic }}</span>@endif
                                        @if($act->uic && $act->pic)<span class="opacity-25">|</span>@endif
                                        @if($act->pic)<span><i class="fa-solid fa-user me-1 opacity-50"></i>{{ $act->pic }}</span>@endif
                                    </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-muted" style="font-size: 0.72rem;">
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-folder-open opacity-40"></i>
                                        <span class="fw-medium text-dark opacity-75">{{ $act->milestone?->subProgram?->program?->name ?? 'N/A' }}</span>
                                        <i class="fa-solid fa-angle-right opacity-25" style="font-size: 0.5rem;"></i>
                                        <span>{{ $act->milestone?->subProgram?->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 7px; background: #e2e8f0;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $act->progress }}%;
                                                        background: {{ $act->progress >= 100 ? '#059669' : ($act->progress >= 50 ? '#4f46e5' : '#f97316') }};">
                                            </div>
                                        </div>
                                        <span class="fw-bold text-dark" style="font-size: 0.72rem; min-width: 32px;">{{ $act->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="badge rounded-pill px-2 py-1 fw-semibold border" style="font-size: 0.6rem; color: {{ $manualColor }}; background: {{ $manualColor }}15; border-color: {{ $manualColor }}33 !important;">
                                            {{ $act->status }}
                                        </span>
                                        <span class="badge rounded-2 px-2 py-1 fw-semibold text-white" style="font-size: 0.6rem; background: {{ $sysColor }}; letter-spacing: 0.04em;">
                                            SYS: {{ $sys }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <div class="text-muted rounded-2 d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.68rem; background: #f1f5f9; border: 1px solid #e2e8f0;">
                                        <i class="fa-regular fa-calendar opacity-50"></i>
                                        <span>{{ $act->start_date ? $act->start_date->format('d M') : '-' }}</span>
                                        <span class="opacity-40">-</span>
                                        <span>{{ $act->end_date ? $act->end_date->format('d M') : '-' }}</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-5 text-center">
                    <div class="mb-3 opacity-40" style="font-size: 3.5rem; color: #059669;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="fw-semibold text-dark mb-1 fs-6">All caught up!</p>
                    <p class="text-muted" style="font-size: 0.8rem;">There are no pending activities at the moment.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
