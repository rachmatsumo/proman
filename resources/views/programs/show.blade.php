@extends('layouts.app')

@section('title', 'Program Detail - ProMan')
@section('header_title', $program->name)

@section('header_actions')
    <div class="d-flex gap-2">
        <!-- Export Excel -->
        <div class="dropdown">
            <button class="btn btn-success btn-sm shadow-sm fw-medium px-3 d-flex align-items-center dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-file-export me-2"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('programs.export-excel', $program->id) }}">
                        <i class="fa-solid fa-file-excel text-success me-2" style="width: 20px;"></i> 
                        <span>Excel Export</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('programs.export-pdf', $program->id) }}">
                        <i class="fa-solid fa-file-pdf text-danger me-2" style="width: 20px;"></i> 
                        <span>PDF Export</span>
                    </a>
                </li>
            </ul>
        </div>
        <a href="{{ route('programs.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-medium px-3 d-flex align-items-center">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </div>
@endsection

{{-- Frappe Gantt — Reverted from FullCalendar per user request --}}
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.flatpickr-range { background-color: #fff; cursor: pointer; }
.flatpickr-calendar { border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); font-family: inherit; }
.flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: #6366f1; border-color: #6366f1; }
.flatpickr-day.inRange { background: #e0e7ff; border-color: #e0e7ff; color: #3730a3; box-shadow: -4px 0 0 #e0e7ff, 4px 0 0 #e0e7ff; }
.flatpickr-day.selected.startRange, .flatpickr-day.startRange.startRange { border-radius: 8px 0 0 8px; }
.flatpickr-day.selected.endRange, .flatpickr-day.endRange.endRange { border-radius: 0 8px 8px 0; }
</style>
@endpush
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $userPivot = $program->members()->where('user_id', $currentUser->id)->first();
    $userRole = 'none';
    if ($currentUser->id == 1 || $currentUser->isAdmin()) {
        $userRole = 'administrator';
    } elseif ($userPivot) {
        $userRole = $userPivot->pivot->role;
    }

    $allActivities = $program->subPrograms->flatMap(fn($s) => $s->milestones->flatMap(fn($m) => $m->activities));
    $totalActs = $allActivities->count();
    $doneActs  = $allActivities->where('progress', 100)->count();

    $allSubActivities = $allActivities->flatMap(fn($a) => $a->subActivities);
    $totalSubActs = $allSubActivities->count();

    $totalMilestones = $program->subPrograms->flatMap(fn($s) => $s->milestones)->count();

    // Pre-compute attachment data
    $attachmentsData = [];
    foreach ($program->subPrograms as $sub) {
        $attachmentsData[] = [
            'attachable_type' => 'sub_program',
            'attachable_id'   => $sub->id,
            'attachments'     => $sub->attachments->values(),
        ];
        foreach ($sub->milestones as $ms) {
            $attachmentsData[] = [
                'attachable_type' => 'milestone',
                'attachable_id'   => $ms->id,
                'attachments'     => $ms->attachments->values(),
            ];
            foreach ($ms->activities as $act) {
                $attachmentsData[] = [
                    'attachable_type' => 'activity',
                    'attachable_id'   => $act->id,
                    'attachments'     => $act->attachments->values(),
                ];
            }
        }
    }

    // ---- Cascading bobot helpers ----
    // Activity: bobot not used at display level, but is used by parent milestone
    //   milestone_progress = if ALL activities have bobot: sum(act.progress * act.bobot/100), else avg(act.progress)
    //   sub_progress       = if ALL milestones have bobot: sum(ms_progress * ms.bobot/100),   else avg(ms_progress)
    //   program_progress   = if ALL sub programs have bobot: sum(sub_prog * sub.bobot/100),   else avg(sub_prog)

    $calcMsProgress = function($ms) {
        $acts = $ms->activities;
        if ($acts->isEmpty()) return 0;
        return round($acts->avg('progress') ?? 0);
    };

    $calcSubProgress = function($sub) use ($calcMsProgress) {
        $milestones = $sub->milestones;
        if ($milestones->isEmpty()) return 0;
        $allHave = $milestones->every(fn($m) => $m->bobot !== null);
        $msProg  = $milestones->map(fn($m) => $calcMsProgress($m));
        if ($allHave) {
            $total = 0;
            foreach ($milestones as $i => $m) { $total += $msProg[$i] * $m->bobot / 100; }
            return min(100, $total);
        }
        return $msProg->avg() ?? 0;
    };

    $subs = $program->subPrograms;
    $allSubsHaveBobot = $subs->isNotEmpty() && $subs->every(fn($s) => $s->bobot !== null);
    if ($allSubsHaveBobot) {
        $weighted = 0;
        foreach ($subs as $s) { $weighted += $calcSubProgress($s) * $s->bobot / 100; }
        $avgProgress = round(min(100, $weighted));
    } elseif ($totalActs > 0) {
        $avgProgress = round($allActivities->avg('progress'));
    } else {
        $avgProgress = 0;
    }

    $sysStatusCount = ['Upcoming' => 0, 'Active' => 0, 'Delayed' => 0, 'Completed' => 0];
    foreach($allActivities as $a) {
        $sys = $a->system_status;
        if(isset($sysStatusCount[$sys])) $sysStatusCount[$sys]++;
    }
@endphp

<div class="container-xl d-flex flex-column gap-4">

    {{-- ===== PROGRAM HERO ===== --}}
    <div class="rounded-3 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b, #312e81, #4f46e5);">
        <div class="p-4 p-md-5 position-relative text-white">
            <div class="position-absolute top-0 end-0 pe-4 pt-3" style="font-size: 14rem; line-height: 1;opacity:.2">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
            <div class="position-relative">
                <div class="d-flex flex-column align-items-md-start justify-content-between gap-2" id="program-header-content">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(255,255,255,0.15);">
                                <i class="fa-solid fa-folder-open fs-4"></i>
                            </div>
                            <div>
                                <p class="text-white opacity-60 mb-0" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em;">Inisiatif Program</p>
                                <h2 class="fs-4 fw-bold text-white mb-0">@if($program->prefix){{ $program->prefix }} @endif{{ $program->name }}</h2>
                                @if($program->theme)
                                    <div class="mt-1 mb-2">
                                        <span class="badge rounded-pill fw-semibold" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white; font-size: 0.72rem; letter-spacing: 0.05em;">
                                            <i class="fa-solid fa-tag me-1 opacity-75"></i>{{ $program->theme }}
                                        </span>
                                    </div>
                                @endif
                                @if($program->description)
                                    <p class="text-white opacity-75 mb-3" style="font-size: 0.85rem; max-width: 600px;">{{ $program->description }}</p>
                                @endif
                                <div class="d-flex align-items-center gap-2 text-white opacity-75" style="font-size: 0.78rem;">
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    <span>{{ $program->start_date ? $program->start_date->format('d M Y') : 'N/A' }}</span>
                                    <span class="opacity-40 mx-1">→</span>
                                    <span>{{ $program->end_date ? $program->end_date->format('d M Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0 flex-wrap w-100 justify-content-end">
                        {{-- Edit Program --}}
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button" class="btn btn-sm fw-semibold d-flex align-items-center gap-2 px-3"
                                style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: white;"
                                onclick="openEditProgram({{ $program->id }}, '{{ addslashes($program->prefix ?? '') }}', '{{ addslashes($program->theme ?? '') }}', '{{ addslashes($program->name) }}', '{{ addslashes($program->description ?? '') }}', '{{ $program->start_date ? $program->start_date->format('Y-m-d') : '' }}', '{{ $program->end_date ? $program->end_date->format('Y-m-d') : '' }}')"
                                data-bs-toggle="modal" data-bs-target="#modalEditProgram">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        @endif
                        {{-- Add Sub Program --}}
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button" class="btn btn-sm fw-semibold d-flex align-items-center gap-2 px-3"
                                style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;"
                                data-bs-toggle="modal" data-bs-target="#modalAddSubProgram">
                            <i class="fa-solid fa-plus"></i> Sub Program
                        </button>
                        @endif
                        {{-- Delete Program --}}
                        @if($userRole === 'administrator')
                        <button type="button" class="btn btn-sm fw-semibold d-flex align-items-center gap-2 px-3"
                                style="background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); color: #fca5a5;"
                                onclick="if(confirm('Hapus program ini secara permanen?')) deleteHierarchyItem('{{ route('programs.destroy', $program->id) }}', '', () => { window.location.href = '{{ route('projects.gantt') }}'; })">
                            <i class="fa-solid fa-trash-can"></i> Delete
                        </button>
                        @endif
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    @php
                        $heroStats = [
                            ['label' => 'Sub Programs', 'value' => $program->subPrograms->count(), 'icon' => 'fa-diagram-project'],
                            ['label' => 'Milestones',   'value' => $totalMilestones,               'icon' => 'fa-bullseye'],
                            ['label' => 'Activities',   'value' => $totalActs,                     'icon' => 'fa-list-check'],
                            ['label' => 'Sub Activities', 'value' => $totalSubActs,                'icon' => 'fa-list-ul'],
                            ['label' => 'Completed',    'value' => $doneActs,                      'icon' => 'fa-circle-check'],
                            ['label' => 'Avg Progress', 'value' => $avgProgress . '%',             'icon' => 'fa-gauge-high'],
                        ];
                    @endphp
                    @foreach($heroStats as $stat)
                    <div class="col-6 col-md">
                        <div class="rounded-3 p-3" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid {{ $stat['icon'] }} opacity-60" style="font-size: 0.8rem;"></i>
                                <span class="text-white opacity-60" style="font-size: 0.50rem; text-transform: uppercase; letter-spacing: 0.07em;">{{ $stat['label'] }}</span>
                            </div>
                            <p class="fw-black text-white mb-0" style="font-size: 1.6rem; letter-spacing: -1px;">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="px-4 px-md-5 py-3" style="background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.08);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-white opacity-60" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em;">Overall Progress</span>
                <span class="text-white fw-bold" style="font-size: 0.8rem;">{{ $avgProgress }}%</span>
            </div>
            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.12);">
                <div class="progress-bar" style="width: {{ $avgProgress }}%; background: linear-gradient(to right, #a5b4fc, #e0e7ff);"></div>
            </div>
        </div>
    </div>

    {{-- ===== SYSTEM STATUS CHIPS ===== --}}
    @php
        $statusSummary = [
            'Upcoming'  => ['count' => 0, 'groups' => []],
            'Active'    => ['count' => 0, 'groups' => []],
            'Delayed'   => ['count' => 0, 'groups' => []],
            'Completed' => ['count' => 0, 'groups' => []],
        ];

        $programPrefix = $program->prefix ? $program->prefix . '.' : '1.';

        foreach($program->subPrograms as $subIdx => $sub) {
            $subNum = $programPrefix . ($subIdx + 1);
            
            $mGroup = 1;
            $mCounter = 0;
            
            foreach($sub->milestones as $ms) {
                if ($ms->type === 'divider') {
                    $mGroup++;
                    $mCounter = 0;
                    continue;
                }
                
                $mCounter++;
                $msNum = 'M.' . $mGroup . '.' . $mCounter;
                $actPrefix = $mGroup . '.' . $mCounter;

                foreach($ms->activities as $actIdx => $act) {
                    $sys = $act->system_status;
                    if(isset($statusSummary[$sys])) {
                        $statusSummary[$sys]['count']++;
                        $subName = $sub->name;
                        $msName  = $ms->name;

                        $actNum = $actPrefix . '.' . ($actIdx + 1);

                        if(!isset($statusSummary[$sys]['groups'][$subName])) {
                            $statusSummary[$sys]['groups'][$subName] = [
                                'num' => $subNum,
                                'milestones' => []
                            ];
                        }
                        if(!isset($statusSummary[$sys]['groups'][$subName]['milestones'][$msName])) {
                            $statusSummary[$sys]['groups'][$subName]['milestones'][$msName] = [
                                'num' => $msNum,
                                'activities' => []
                            ];
                        }

                        $statusSummary[$sys]['groups'][$subName]['milestones'][$msName]['activities'][] = [
                            'num'  => $actNum,
                            'name' => $act->name,
                            'pct'  => $act->progress
                        ];
                    }
                }
            }
        }

        $chipConfig = [
            'Upcoming'  => ['color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-clock'],
            'Active'    => ['color' => '#4f46e5', 'bg' => '#eef2ff', 'icon' => 'fa-bolt'],
            'Delayed'   => ['color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'fa-triangle-exclamation'],
            'Completed' => ['color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-circle-check'],
        ];
    @endphp

    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted fw-semibold me-1" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.07em;">Auto Status:</span>

        @foreach($chipConfig as $label => $c)
        @php
            $summary   = $statusSummary[$label];
            $chipId    = 'chip-' . strtolower($label);
        @endphp
        <div id="{{ $chipId }}"
             class="d-inline-flex align-items-center gap-1 rounded-pill px-3 py-1 fw-semibold border chip-status"
             style="font-size: 0.72rem; color: {{ $c['color'] }}; background: {{ $c['bg'] }}; border-color: {{ $c['color'] }}33 !important; cursor: pointer;">
            <i class="fa-solid {{ $c['icon'] }}"></i>
            <span>{{ $label }}</span>
            <span class="fw-black ms-1 px-1 rounded-pill" style="background: {{ $c['color'] }}18;">{{ $summary['count'] }}</span>
        </div>
        {{-- hidden popover content for this chip --}}
        <div id="{{ $chipId }}-content" class="d-none">
            @if($summary['count'] === 0)
                <div class="text-muted fst-italic p-3" style="font-size:0.75rem;">Tidak ada aktivitas dengan status ini.</div>
            @else
                <div class="popover-scroll-container" style="max-height:350px; width:300px; overflow-y:auto; overflow-x:hidden;">
                    @foreach($summary['groups'] as $subName => $subGroup)
                        <div class="sub-group mb-0">
                            <div class="bg-light border-bottom px-3 py-2 d-flex justify-content-between align-items-center sticky-top" style="z-index: 10;">
                                <div class="text-uppercase fw-bold text-primary text-truncate me-2" style="font-size:0.65rem; letter-spacing:0.02em;">
                                    {{ $subName }}
                                </div>
                                <span class="badge rounded-pill bg-white text-primary border border-primary-subtle font-monospace" style="font-size: 0.6rem;">{{ $subGroup['num'] }}</span>
                            </div>
                            
                            <div class="px-3 py-2">
                                @foreach($subGroup['milestones'] as $msName => $msGroup)
                                    <div class="ms-group mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-secondary-subtle text-secondary border-0 px-1 font-monospace" style="font-size: 0.58rem; min-width: 35px; text-align: center;">{{ $msGroup['num'] }}</span>
                                            <div class="fw-bold text-dark" style="font-size:0.7rem; line-height: 1.2;">{{ $msName }}</div>
                                        </div>
                                        
                                        <div class="act-list ms-1">
                                            @foreach($msGroup['activities'] as $act)
                                                <div class="d-flex align-items-start gap-2 mb-2" style="font-size:0.72rem; line-height: 1.4;">
                                                    <span class="text-muted font-monospace opacity-75" style="font-size:0.6rem; min-width: 38px; padding-top: 1px;">{{ $act['num'] }}</span>
                                                    <div class="flex-grow-1">
                                                        <span class="text-secondary">{{ $act['name'] }}</span>
                                                        <span class="badge text-bg-light border border-light-subtle ms-1 fw-normal" style="font-size: 0.6rem;">{{ $act['pct'] }}%</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endforeach
    </div>



    {{-- ===== TABS NAVIGATION ===== --}}
    <ul class="nav nav-pills mb-4 gap-2 pb-3 border-bottom border-light-subtle" id="programTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2" id="hierarki-tab" data-bs-toggle="pill" data-bs-target="#hierarki" type="button" role="tab" aria-controls="hierarki" aria-selected="true" style="transition: all 0.2s;">
                <i class="fa-solid fa-diagram-project"></i> Hierarki
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2 ajax-tab" id="timeline-tab" data-bs-toggle="pill" data-bs-target="#timeline" type="button" role="tab" aria-controls="timeline" aria-selected="false" data-url="{{ route('programs.partial-gantt', $program->id) }}" style="transition: all 0.2s;">
                <i class="fa-solid fa-chart-gantt"></i> Timeline
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2 ajax-tab" id="calendar-tab" data-bs-toggle="pill" data-bs-target="#calendar" type="button" role="tab" aria-controls="calendar" aria-selected="false" data-url="{{ route('programs.partial-calendar', $program->id) }}" style="transition: all 0.2s;">
                <i class="fa-solid fa-calendar-days"></i> Calendar
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2" id="dokumen-tab" data-bs-toggle="pill" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false" style="transition: all 0.2s;">
                <i class="fa-solid fa-folder-open"></i> Document
                @php $totalAttachments = collect($attachmentsData)->pluck('attachments')->flatten(1)->count(); @endphp
                <span class="badge rounded-pill ms-1" style="background: rgba(0,0,0,0.1); color: inherit;">{{ $totalAttachments ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2" id="member-tab" data-bs-toggle="pill" data-bs-target="#member" type="button" role="tab" aria-controls="member" aria-selected="false" style="transition: all 0.2s;">
                <i class="fa-solid fa-users-gear"></i> Members
                <span class="badge rounded-pill ms-1" style="background: rgba(0,0,0,0.1); color: inherit;">{{ $program->members->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold fs-sm px-4 py-2 rounded-pill d-flex align-items-center gap-2" id="riwayat-tab" data-bs-toggle="pill" data-bs-target="#riwayat" type="button" role="tab" aria-controls="riwayat" aria-selected="false" style="transition: all 0.2s;">
                <i class="fa-solid fa-clock-rotate-left"></i> History
            </button>
        </li>
    </ul>

    <style>
        .nav-pills .nav-link { color: #64748b; background: transparent; }
        .nav-pills .nav-link:hover { background: #f1f5f9; color: #334155; }
        .nav-pills .nav-link.active { background: #4f46e5; color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
        
        .icon-collapse { transition: transform 0.3s ease; }
        [aria-expanded="false"] .icon-collapse { transform: rotate(-90deg); }

        /* Status Popover Styles */
        .status-popover { max-width: 330px !important; border: 0 !important; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important; }
        .status-popover .popover-header { background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 800; color: #1e293b; padding: 12px 16px; font-size: 0.85rem; }
        .status-popover .popover-body { padding: 0 !important; }
        .status-popover .popover-scroll-container::-webkit-scrollbar { width: 6px; }
        .status-popover .popover-scroll-container::-webkit-scrollbar-track { background: #f1f5f9; }
        .status-popover .popover-scroll-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .status-popover .popover-scroll-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <div class="tab-content" id="programTabsContent">
        {{-- TAB 1: HIERARKI --}}
        <div class="tab-pane fade show active" id="hierarki" role="tabpanel" aria-labelledby="hierarki-tab">
            <div class="d-flex flex-column gap-3" id="sub-programs-container">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2 justify-content-between">
            <h3 class="fw-bold fs-6 mb-0 text-dark d-flex align-items-center">
                <i class="fa-solid fa-sitemap text-primary me-2 opacity-75"></i>Hierarchy Structure
            </h3>
            <div class="flex-grow-1 border-bottom border-secondary border-opacity-25 ms-2 me-3 d-none d-md-block"></div>
            <div class="d-flex gap-2 ms-auto align-items-center flex-wrap">
                <div class="input-group input-group-sm rounded-pill overflow-hidden shadow-sm" style="width: 250px; border: 1px solid #cbd5e1;">
                    <span class="input-group-text bg-white border-0 text-muted px-3"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="hierarchySearch" class="form-control border-0 shadow-none px-1" placeholder="Cari nama aktivitas/milestone..." style="font-size: 0.75rem;">
                </div>
                <button type="button" class="btn btn-sm btn-light border-light-subtle shadow-sm rounded-pill fw-semibold px-3" onclick="expandAll()" style="font-size: 0.75rem; color: #4f46e5;">
                    <i class="fa-solid fa-angles-down me-1"></i> Expand All
                </button>
                <button type="button" class="btn btn-sm btn-light border-light-subtle shadow-sm rounded-pill fw-semibold px-3" onclick="collapseAll()" style="font-size: 0.75rem; color: #64748b;">
                    <i class="fa-solid fa-angles-up me-1"></i> Collapse All
                </button>
            </div>
        </div>
        
        <div id="noHierarchyResults" class="text-center py-5 d-none">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-magnifying-glass text-muted fs-4"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No results found</h5>
            <p class="text-muted small">No activities or milestones match your search term.</p>
        </div>

        @forelse($program->subPrograms as $sub)
        @php
            $subActs  = $sub->milestones->flatMap(fn($m) => $m->activities);
            $subTotal = $subActs->count();
            $subAvg   = round($calcSubProgress($sub));
        @endphp
        <div class="card shadow-sm border-0 overflow-hidden sub-card mb-3 selectable-row" data-name="{{ strtolower($sub->name) }}" data-id="{{ $sub->id }}">
            {{-- Sub Program Header --}}
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center gap-3"
                 style="background: linear-gradient(to right, #1e3a5f, #1d4ed8); border: none;">
                @if($userRole === 'administrator' || $userRole === 'manager')
                <div class="sub-drag-handle py-2" style="cursor: grab;">
                    <i class="fa-solid fa-grip-vertical text-white opacity-50"></i>
                </div>
                @endif
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseSub{{ $sub->id }}" aria-expanded="false">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 36px; height: 36px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fa-solid fa-chevron-down text-white icon-collapse transition-transform" id="iconSub{{ $sub->id }}"></i>
                    </div>
                    <div>
                        <p class="text-white opacity-60 mb-0" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em;">Sub Program</p>
                        <h5 class="fw-bold text-white mb-0 fs-6"><span class="sub-num">{{ $program->prefix ? $program->prefix . '.' : '' }}{{ $loop->iteration }}</span> {{ $sub->name }}</h5>
                        @if($sub->description)
                            <div class="text-white-50 mt-1" style="font-size: 0.68rem;">{{ Str::limit($sub->description, 80) }}</div>
                        @endif
                        @if($sub->start_date && $sub->end_date)
                            <div class="text-white-50 mt-1 d-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                <i class="fa-regular fa-calendar-days"></i>
                                <span>{{ $sub->start_date->format('d M Y') }} - {{ $sub->end_date->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <div class="text-center d-none d-md-block">
                            <p class="text-white opacity-60 mb-0" style="font-size: 0.6rem; text-transform: uppercase;">Progress</p>
                            <p class="text-white fw-bold mb-0" style="font-size: 0.9rem;">{{ $subAvg }}%</p>
                        </div>
                        <div class="text-center d-none d-md-block">
                            <p class="text-white opacity-60 mb-0" style="font-size: 0.6rem; text-transform: uppercase;">Activities</p>
                            <p class="text-white fw-bold mb-0" style="font-size: 0.9rem;">{{ $subActs->where('progress',100)->count() }}/{{ $subTotal }}</p>
                        </div>
                        @if($sub->bobot !== null)
                        <div class="text-center d-none d-md-block">
                            <p class="text-white opacity-60 mb-0" style="font-size: 0.6rem; text-transform: uppercase;">Bobot</p>
                            <p class="text-white fw-bold mb-0" style="font-size: 0.9rem;">{{ $sub->bobot }}%</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    {{-- Edit Sub Program --}}
                    @if($userRole === 'administrator' || $userRole === 'manager')
                    <button type="button"
                            class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                            style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #bfdbfe; font-size: 0.72rem;"
                            data-bs-toggle="modal" data-bs-target="#modalEditSubProgram"
                            onclick="openEditSubProgram({{ $sub->id }}, {{ $program->id }}, '{{ addslashes($sub->name) }}', '{{ $sub->bobot ?? '' }}', '{{ addslashes($sub->description ?? '') }}', '{{ $sub->start_date ? $sub->start_date->format('Y-m-d') : '' }}', '{{ $sub->end_date ? $sub->end_date->format('Y-m-d') : '' }}')">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                    <button type="button"
                            class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                            style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #bfdbfe; font-size: 0.72rem;"
                            onclick="openDuplicateModal('sub_program', {{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                        <i class="fa-solid fa-copy"></i> Duplikat
                    </button>
                    @endif
                    {{-- Attachments Sub Program --}}
                    <button type="button"
                            class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                            style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: #fbbf24; font-size: 0.72rem;"
                            onclick="openAttachmentModal('sub_program', {{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                        <i class="fa-solid fa-paperclip"></i>
                        Lampiran
                        @if($sub->attachments->count() > 0)
                        <span class="badge rounded-pill ms-1" style="background: #fbbf24; color: #1e1b4b; font-size: 0.6rem;">{{ $sub->attachments->count() }}</span>
                        @endif
                    </button>
                    {{-- Add Milestone & Key Result --}}
                    @if($userRole === 'administrator' || $userRole === 'manager')
                    <div class="d-flex gap-1">

                        <button type="button"
                                class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white; font-size: 0.72rem;"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="openAddMilestone({{ $sub->id }}, 'milestone')">
                            <i class="fa-solid fa-plus"></i> Milestone
                        </button>
                        <button type="button"
                                class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                style="background: rgba(255,193,7,0.15); border: 1px solid rgba(255,193,7,0.3); color: #fbbf24; font-size: 0.72rem;"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="openAddMilestone({{ $sub->id }}, 'divider')">
                            <i class="fa-solid fa-minus"></i> Section Divider
                        </button>
                    </div>
                    @endif
                    {{-- Delete Sub Program --}}
                    @if($userRole === 'administrator')
                    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 px-2"
                            style="background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; font-size: 0.72rem;"
                            onclick="deleteHierarchyItem('{{ route('sub_programs.destroy', $sub->id) }}', 'Hapus sub program ini?', () => { document.querySelector('.sub-card[data-id=\'{{ $sub->id }}\']').remove(); })">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    @endif
                </div>
            </div>

            <div id="collapseSub{{ $sub->id }}" class="collapse hierarchy-collapse sub-collapse">
                <div style="height: 4px; background: #e2e8f0;">
                    <div style="width: {{ $subAvg }}%; height: 100%; background: linear-gradient(to right, #3b82f6, #6366f1);"></div>
                </div>
                @php 
                    $mGroup = 1; 
                    $mCount = 0; 
                    $krCount = 0; 

                    $actualMilestones = $sub->milestones->where('type', '!=', 'key_result');
                    $keyResults = $sub->milestones->where('type', '===', 'key_result');
                    $milestoneGroups = [
                        'milestones' => $actualMilestones,
                        'key_results' => $keyResults
                    ];
                @endphp
                @foreach($milestoneGroups as $groupType => $msCollection)
                <div class="card-body p-3 d-flex flex-column gap-3 bg-light sub-body {{ $groupType === 'key_results' ? 'key-results-container border-top border-2' : 'milestones-container' }}" data-sub-id="{{ $sub->id }}" id="{{ $groupType }}-{{ $sub->id }}" {!! $groupType === 'key_results' ? 'style="border-style: dashed !important; border-color: #cbd5e1 !important; min-height: 50px;"' : '' !!}>
                @if($groupType === 'key_results')
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="text-danger fw-bold mb-0" style="font-size: 0.75rem;"><i class="fa-solid fa-bullseye me-1"></i> KEY RESULTS</h6>
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button"
                                class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                style="font-size: 0.7rem; background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626;"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="openAddMilestone({{ $sub->id }}, 'key_result'); document.getElementById('milestoneModalSubLabel').textContent = 'Sub Program: {{ addslashes($sub->name) }}';">
                            <i class="fa-solid fa-plus"></i> Tambah KR
                        </button>
                        @endif
                    </div>
                @endif
                @forelse($msCollection as $ms)
                @php
                    if($ms->type === 'divider') {
                        // A divider bumps the group number and resets the count for milestones ONLY
                        $mGroup++;
                        $mCount = 0;
                        
                        $msNum = ''; // Dividers have no number
                        $actPrefix = '';
                    } else {
                        if($ms->type === 'key_result') {
                            $krCount++;
                            $msNum = 'KR.' . $krCount;
                            $actPrefix = $krCount;
                        } else {
                            $mCount++;
                            $msNum = 'M.' . $mGroup . '.' . $mCount;
                            $actPrefix = $mGroup . '.' . $mCount;
                        }
                    }

                    $msActs  = $ms->activities;
                    $msTotal = $msActs->count();
                    $msAvg   = round($calcMsProgress($ms));
                @endphp
                
                @if($ms->type === 'divider')
                    {{-- DIVIDER RENDER --}}
                    <div class="d-flex align-items-center mb-1 mt-2 milestone-card" data-sub="{{ $sub->id }}" data-id="{{ $ms->id }}" id="ms-{{ $ms->id }}">
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <i class="fa-solid fa-grip-vertical text-muted opacity-25 me-3 ms-drag-handle" style="cursor: grab;"></i>
                        @endif
                        <div class="flex-grow-1 border-bottom border-2 border-warning opacity-50"></div>
                        <div class="mx-3 fw-bold text-warning text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.1em;">
                            {{ $ms->name }}
                        </div>
                        <div class="flex-grow-1 border-bottom border-2 border-warning opacity-50"></div>
                        <div class="ms-3">
                            @if($userRole === 'administrator' || $userRole === 'manager')
                            <button type="button" class="btn btn-sm p-1" style="color: #4338ca; background: none; border: none; font-size: 0.75rem;" title="Edit Section Name"
                                    data-bs-toggle="modal" data-bs-target="#modalEditMilestone"
                                    onclick="openEditMilestone({{ $ms->id }}, {{ $sub->id }}, '{{ addslashes($ms->name) }}', '{{ $ms->bobot ?? '' }}', '{{ addslashes($ms->description ?? '') }}', '{{ $ms->start_date ? $ms->start_date->format('Y-m-d') : '' }}', '{{ $ms->end_date ? $ms->end_date->format('Y-m-d') : '' }}', '{{ $ms->type }}')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            @endif
                            @if($userRole === 'administrator')
                            <button type="button" class="btn btn-sm p-1" style="color: #ef4444; background: none; border: none; font-size: 0.75rem;" title="Hapus Section"
                                    onclick="deleteHierarchyItem('{{ route('milestones.destroy', $ms->id) }}', 'Hapus section divider ini?', () => { document.querySelector('#ms-{{ $ms->id }}').remove(); recalculateHierarchyNumbering(); })">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                            @endif
                        </div>
                        <span class="ms-num d-none" data-type="divider"></span>
                    </div>
                @else
                    {{-- REGULAR MILESTONE / KR RENDER --}}
                <div class="card border-0 shadow-sm overflow-hidden milestone-card mb-3" data-name="{{ strtolower($ms->name) }}" data-id="{{ $ms->id }}">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <div class="ms-drag-handle py-1 px-1 me-1" style="cursor: grab;">
                            <i class="fa-solid fa-grip-vertical text-muted opacity-50"></i>
                        </div>
                        @endif
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseMs{{ $ms->id }}" aria-expanded="false">
                            <div class="rounded-2 d-flex align-items-center justify-content-center transition-transform icon-collapse"
                                 style="width: 28px; height: 28px; background: {{ $ms->type === 'key_result' ? '#fee2e2' : '#dbeafe' }}; border: 1px solid {{ $ms->type === 'key_result' ? '#fecaca' : '#bfdbfe' }};" id="iconMs{{ $ms->id }}">
                                <i class="fa-solid {{ $ms->type === 'key_result' ? 'fa-bullseye' : 'fa-chevron-down' }}" style="color: {{ $ms->type === 'key_result' ? '#dc2626' : '#3b82f6' }}; font-size: 0.7rem;"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0" style="font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.07em;">{{ $ms->type === 'key_result' ? 'Key Result' : 'Milestone' }}</p>
                                <h6 class="fw-semibold text-dark mb-0" style="font-size: 0.85rem;"><span class="ms-num" data-type="{{ $ms->type }}">{{ $msNum }}</span> {{ $ms->name }}
                                    @if($ms->bobot !== null)
                                    <span class="ms-1 badge rounded-pill fw-semibold" style="background: #ede9fe; color: #6d28d9; font-size: 0.6rem; border: 1px solid #ddd6fe; vertical-align: middle;">
                                        <i class="fa-solid fa-weight-hanging me-1" style="font-size: 0.5rem;"></i>{{ $ms->bobot }}%
                                    </span>
                                    @endif
                                </h6>
                                @if($ms->description)
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">{{ Str::limit($ms->description, 70) }}</div>
                                @endif
                                @if($ms->start_date && $ms->end_date)
                                    <div class="text-muted mt-1 d-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                        <i class="fa-regular fa-calendar-days"></i>
                                        <span>{{ $ms->start_date->format('d M Y') }} - {{ $ms->end_date->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($ms->bobot !== null)
                            <span class="badge rounded-pill fw-semibold" style="background: #ede9fe; color: #6d28d9; font-size: 0.65rem; border: 1px solid #ddd6fe;">
                                <i class="fa-solid fa-weight-hanging me-1" style="font-size: 0.55rem;"></i>Bobot {{ $ms->bobot }}%
                            </span>
                            @endif
                            @if($msTotal > 0)
                            <span class="badge rounded-pill fw-semibold" style="background: #dbeafe; color: #1d4ed8; font-size: 0.65rem; border: 1px solid #bfdbfe;">
                                {{ $msAvg }}% avg
                            </span>
                            @endif
                            {{-- Edit Milestone --}}
                            @if($userRole === 'administrator' || $userRole === 'manager')
                            <button type="button"
                                    class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                    style="background: #e0e7ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 0.68rem;"
                                    data-bs-toggle="modal" data-bs-target="#modalEditMilestone"
                                    onclick="openEditMilestone({{ $ms->id }}, {{ $ms->sub_program_id }}, '{{ addslashes($ms->name) }}', '{{ $ms->bobot ?? '' }}', '{{ addslashes($ms->description ?? '') }}', '{{ $ms->start_date ? $ms->start_date->format('Y-m-d') : '' }}', '{{ $ms->end_date ? $ms->end_date->format('Y-m-d') : '' }}')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                    style="background: #e0e7ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 0.68rem;"
                                    title="Duplikat Milestone"
                                    onclick="openDuplicateModal('milestone', {{ $ms->id }}, '{{ addslashes($ms->name) }}')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                            @endif
                            {{-- Attachments Milestone --}}
                            <button type="button"
                                    class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                    style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; font-size: 0.68rem;"
                                    onclick="openAttachmentModal('milestone', {{ $ms->id }}, '{{ addslashes($ms->name) }}')">
                                <i class="fa-solid fa-paperclip"></i>
                                Lampiran
                                @if($ms->attachments->count() > 0)
                                <span class="badge rounded-pill" style="background: #f59e0b; color: white; font-size: 0.58rem;">{{ $ms->attachments->count() }}</span>
                                @endif
                            </button>
                            {{-- Add Activity --}}
                            @if($userRole === 'administrator' || $userRole === 'manager')
                            <button type="button"
                                    class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                                    style="background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; font-size: 0.68rem;"
                                    data-bs-toggle="modal" data-bs-target="#modalAddActivity"
                                    onclick="setActivityMilestone({{ $ms->id }}, '{{ addslashes($ms->name) }}')">
                                <i class="fa-solid fa-plus"></i> Activity
                            </button>
                            @endif
                            {{-- Delete Milestone --}}
                            @if($userRole === 'administrator')
                            <button type="button" class="btn btn-sm p-1" style="color: #ef4444; background: none; border: none; font-size: 0.75rem;" title="Hapus Milestone"
                                    onclick="deleteHierarchyItem('{{ route('milestones.destroy', $ms->id) }}', 'Hapus milestone ini?', () => { document.querySelector('.milestone-card[data-id=\'{{ $ms->id }}\']').remove(); })">
                                <i class="fa-solid fa-times"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                    <div id="collapseMs{{ $ms->id }}" class="collapse hierarchy-collapse ms-collapse">
                    {{-- Activities Table --}}
                    @if($ms->activities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.79rem;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th class="px-4 py-2 fw-semibold border-bottom text-muted" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em;">Activity</th>
                                    <th class="px-3 py-2 fw-semibold border-bottom text-muted text-center" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; width: 150px;">Dates</th>
                                    <th class="px-3 py-2 fw-semibold border-bottom text-muted text-center" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; width: 140px;">Progress</th>
                                    <th class="px-3 py-2 fw-semibold border-bottom text-muted text-center" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; width: 130px;">Status</th>
                                    <th class="px-3 py-2 fw-semibold border-bottom text-muted text-center" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; width: 120px;">Personnel</th>
                                    <th class="px-2 py-2 border-bottom" style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0 activities-container" data-ms-id="{{ $ms->id }}" id="activities-{{ $ms->id }}">
                                @foreach($ms->activities as $act)
                                @php
                                    $manualColor = '#64748b'; $manualBg = '#f1f5f9';
                                    if($act->status == 'To Do')       { $manualColor = '#0891b2'; $manualBg = '#ecfeff'; }
                                    if($act->status == 'On Progress')  { $manualColor = '#d97706'; $manualBg = '#fffbeb'; }
                                    if($act->status == 'On Hold')      { $manualColor = '#f97316'; $manualBg = '#fff7ed'; }
                                    if($act->status == 'Done')         { $manualColor = '#059669'; $manualBg = '#d1fae5'; }
                                    if($act->status == 'Cancelled')    { $manualColor = '#dc2626'; $manualBg = '#fef2f2'; }

                                    $sysColor = '#64748b';
                                    $sys = $act->system_status;
                                    if($sys == 'Active')    $sysColor = '#4f46e5';
                                    if($sys == 'Delayed')   $sysColor = '#dc2626';
                                    if($sys == 'Completed') $sysColor = '#059669';

                                    $barColor = $act->progress >= 100 ? '#059669' : ($act->progress >= 60 ? '#4f46e5' : ($act->progress > 0 ? '#d97706' : '#cbd5e1'));
                                @endphp
                                <tr class="activity-item" data-name="{{ strtolower($act->name) }}" data-id="{{ $act->id }}">
                                    <td class="px-4 py-2">
                                        <div class="d-flex align-items-center">
                                            @if($userRole === 'administrator' || $userRole === 'manager')
                                            <i class="fa-solid fa-grip-vertical text-muted opacity-25 me-3 act-drag-handle" style="cursor: grab;"></i>
                                            @endif
                                            <button class="btn btn-sm text-muted p-0 me-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAct{{ $act->id }}">
                                                <i class="fa-solid fa-chevron-right fs-6 act-toggle-icon"></i>
                                            </button>
                                            <div>
                                                <div class="fw-semibold text-dark"><span class="act-num">{{ $actPrefix }}.{{ $loop->iteration }}</span> {{ $act->name }}</div>
                                                @if($act->description)
                                                    <div class="text-muted" style="font-size: 0.68rem;">{{ Str::limit($act->description, 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center" style="font-size: 0.68rem; color: #64748b;">
                                        <span class="inline-date-range d-inline-flex align-items-center gap-1"
                                              id="act-date-{{ $act->id }}"
                                              data-id="{{ $act->id }}"
                                              data-type="activity"
                                              data-start="{{ $act->start_date ? $act->start_date->format('Y-m-d') : '' }}"
                                              data-end="{{ $act->end_date ? $act->end_date->format('Y-m-d') : '' }}"
                                              title="Klik untuk ubah tanggal"
                                              style="cursor: pointer; padding: 3px 7px; border-radius: 6px; border: 1px dashed #cbd5e1; transition: background 0.15s; white-space: nowrap;"
                                              onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                            <i class="fa-regular fa-calendar me-1" style="font-size: 0.6rem; color: #94a3b8;"></i>
                                            <span class="act-date-display">
                                                @if($act->start_date && $act->end_date)
                                                    {{ $act->start_date->format('d M y') }} → {{ $act->end_date->format('d M y') }}
                                                @else
                                                    <span class="text-muted fst-italic">Set tanggal</span>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 7px; background: #e2e8f0; border-radius: 99px;">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $act->progress }}%; background-color: {{ $barColor }}; border-radius: 99px;"></div>
                                            </div>
                                            <span class="fw-bold" style="font-size: 0.7rem; color: {{ $barColor }}; min-width: 30px;">{{ $act->progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="badge rounded-pill border fw-semibold px-2 py-1"
                                                  style="font-size: 0.6rem; color: {{ $manualColor }}; background: {{ $manualBg }}; border-color: {{ $manualColor }}33 !important;">
                                                {{ $act->status }}
                                            </span>
                                            <span class="badge rounded-2 fw-semibold text-white px-2 py-1"
                                                  style="font-size: 0.58rem; background: {{ $sysColor }}; letter-spacing: 0.04em;">
                                                ⚙ {{ $sys }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center" style="font-size: 0.65rem; color: #64748b;">
                                        @if($act->uic || $act->pic)
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            @if($act->uic)<span><i class="fa-solid fa-building me-1 opacity-50"></i>{{ $act->uic }}</span>@endif
                                            @if($act->pic)<span><i class="fa-solid fa-user me-1 opacity-50"></i>{{ $act->pic }}</span>@endif
                                        </div>
                                        @else
                                        <span class="text-muted opacity-40">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($userRole === 'administrator' || $userRole === 'manager')
                                            <button type="button"
                                                    class="btn btn-sm p-1"
                                                    style="color: #059669; background: none; border: none; font-size: 0.75rem;"
                                                    title="Tambah Sub Activity"
                                                    data-bs-toggle="modal" data-bs-target="#modalAddSubActivity"
                                                    onclick="setSubActivityParent({{ $act->id }}, '{{ addslashes($act->name) }}')">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm p-1" style="color: #4338ca; background: none; border: none; font-size: 0.75rem;" title="Edit Activity"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditActivity"
                                                    onclick="openEditActivity({{ $act->id }}, {{ $ms->id }}, '{{ addslashes($act->name) }}', '{{ addslashes($act->description ?? '') }}', '{{ $act->start_date ? $act->start_date->format('Y-m-d') : '' }}', '{{ $act->end_date ? $act->end_date->format('Y-m-d') : '' }}', '{{ $act->progress }}', '{{ $act->status }}', '{{ addslashes($act->uic ?? '') }}', '{{ addslashes($act->pic ?? '') }}')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm p-1" style="color: #10b981; background: none; border: none; font-size: 0.75rem;" title="Duplikasi Activity"
                                                    onclick="openDuplicateModal('activity', {{ $act->id }}, '{{ addslashes($act->name) }}')">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm p-1"
                                                    style="color: #92400e; background: none; border: none; font-size: 0.75rem;"
                                                    title="Lampiran Activity"
                                                    onclick="openAttachmentModal('activity', {{ $act->id }}, '{{ addslashes($act->name) }}')">
                                                <i class="fa-solid fa-paperclip"></i>
                                                @if($act->attachments->count() > 0)
                                                <span class="badge rounded-pill" style="background: #f59e0b; color: white; font-size: 0.5rem; position: relative; top: -5px;">{{ $act->attachments->count() }}</span>
                                                @endif
                                            </button>
                                            @endif
                                            @if($userRole === 'administrator')
                                            <button type="button" class="btn btn-sm p-1" style="color: #ef4444; background: none; border: none; font-size: 0.75rem;" title="Hapus Activity"
                                                    onclick="deleteHierarchyItem('{{ route('activities.destroy', $act->id) }}', 'Hapus activity ini?', () => { refreshPageContent(); })">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                {{-- Sub Activities Row --}}
                                <tr id="collapseAct{{ $act->id }}" class="collapse collapse-act">
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="bg-light p-3 border-start border-4 border-primary ms-5 mb-3 rounded-end shadow-sm">
                                            @if($act->subActivities->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 0.75rem;">
                                                        <tbody class="sub-activities-container" data-act-id="{{ $act->id }}">
                                                            @foreach($act->subActivities as $subAct)
                                                                @php
                                                                    $subSys = $subAct->system_status;
                                                                    $subSysColor = '#64748b';
                                                                    if($subSys == 'Active')    $subSysColor = '#4f46e5';
                                                                    if($subSys == 'Delayed')   $subSysColor = '#dc2626';
                                                                    if($subSys == 'Completed') $subSysColor = '#059669';
                                                                    $subBarColor = $subAct->progress >= 100 ? '#059669' : ($subAct->progress >= 60 ? '#4f46e5' : ($subAct->progress > 0 ? '#d97706' : '#cbd5e1'));
                                                                @endphp
                                                                <tr class="sub-activity-item border-bottom" data-id="{{ $subAct->id }}">
                                                                    <td style="width: 35%;">
                                                                        <div class="d-flex align-items-center">
                                                                            @if($userRole === 'administrator' || $userRole === 'manager')
                                                                            <i class="fa-solid fa-grip-vertical text-muted opacity-25 me-2 sub-act-drag-handle" style="cursor: grab;"></i>
                                                                            @endif
                                                                            <div>
                                                                                <div class="fw-semibold text-dark"><span class="sub-act-num">{{ $actPrefix }}.{{ $loop->parent->iteration }}.{{ $loop->iteration }}</span> {{ $subAct->name }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center text-muted" style="width: 20%;">
                                                                        <span class="inline-date-range d-inline-flex align-items-center gap-1"
                                                                              id="subact-date-{{ $subAct->id }}"
                                                                              data-id="{{ $subAct->id }}"
                                                                              data-type="sub_activity"
                                                                              data-start="{{ $subAct->start_date ? $subAct->start_date->format('Y-m-d') : '' }}"
                                                                              data-end="{{ $subAct->end_date ? $subAct->end_date->format('Y-m-d') : '' }}"
                                                                              title="Klik untuk ubah tanggal"
                                                                              style="cursor: pointer; padding: 2px 6px; border-radius: 6px; border: 1px dashed #cbd5e1; transition: background 0.15s; font-size: 0.65rem; white-space: nowrap;"
                                                                              onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                                                            <i class="fa-regular fa-calendar me-1" style="font-size: 0.58rem; color: #94a3b8;"></i>
                                                                            <span class="subact-date-display">
                                                                                @if($subAct->start_date && $subAct->end_date)
                                                                                    {{ $subAct->start_date->format('d M y') }} → {{ $subAct->end_date->format('d M y') }}
                                                                                @else
                                                                                    <span class="text-muted fst-italic">Set tanggal</span>
                                                                                @endif
                                                                            </span>
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center" style="width: 15%;">
                                                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                                                            <div class="progress" style="height: 5px; width: 40px; background: #e2e8f0; border-radius: 99px;">
                                                                             <div class="progress-bar" role="progressbar" style="width: {{ $subAct->progress }}%; background-color: {{ $subBarColor }};"></div>
                                                                            </div>
                                                                            <span class="fw-bold" style="font-size: 0.7rem; color: {{ $subBarColor }};">{{ $subAct->progress }}%</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center" style="width: 15%;">
                                                                        <span class="badge rounded-2 fw-semibold text-white px-2 py-1" style="font-size: 0.58rem; background: {{ $subSysColor }};">
                                                                            ⚙ {{ $subSys }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end" style="width: 15%;">
                                                                        <div class="d-flex gap-1 justify-content-end">
                                                                            @if($userRole === 'administrator' || $userRole === 'manager')
                                                                            <button type="button" class="btn btn-sm p-1" style="color: #4338ca; background: none; border: none; font-size: 0.75rem;" title="Edit Sub Activity"
                                                                                    data-bs-toggle="modal" data-bs-target="#modalEditSubActivity"
                                                                                    onclick="openEditSubActivity({{ $subAct->id }}, {{ $act->id }}, '{{ addslashes($subAct->name) }}', '{{ addslashes($subAct->description ?? '') }}', '{{ $subAct->start_date ? $subAct->start_date->format('Y-m-d') : '' }}', '{{ $subAct->end_date ? $subAct->end_date->format('Y-m-d') : '' }}', '{{ $subAct->progress }}', '{{ $subAct->status }}', '{{ addslashes($subAct->uic ?? '') }}', '{{ addslashes($subAct->pic ?? '') }}')">
                                                                                <i class="fa-solid fa-pen-to-square"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-sm p-1" style="color: #10b981; background: none; border: none; font-size: 0.75rem;" title="Duplikasi Sub Activity"
                                                                                    onclick="openDuplicateModal('sub_activity', {{ $subAct->id }}, '{{ addslashes($subAct->name) }}')">
                                                                                <i class="fa-solid fa-copy"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-sm p-1" style="color: #92400e; background: none; border: none; font-size: 0.75rem;" title="Lampiran Sub Activity"
                                                                                    onclick="openAttachmentModal('sub_activity', {{ $subAct->id }}, '{{ addslashes($subAct->name) }}')">
                                                                                <i class="fa-solid fa-paperclip"></i>
                                                                                @if($subAct->attachments->count() > 0)
                                                                                <span class="badge rounded-pill" style="background: #f59e0b; color: white; font-size: 0.5rem; position: relative; top: -5px;">{{ $subAct->attachments->count() }}</span>
                                                                                @endif
                                                                            </button>
                                                                            @endif
                                                                            @if($userRole === 'administrator')
                                                                            <button type="button" class="btn btn-sm p-1" style="color: #ef4444; background: none; border: none; font-size: 0.75rem;" title="Hapus Sub Activity"
                                                                                    onclick="deleteHierarchyItem('{{ route('sub_activities.destroy', $subAct->id) }}', 'Hapus sub activity ini?', () => { refreshPageContent(); })">
                                                                                <i class="fa-solid fa-trash-can"></i>
                                                                            </button>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="small text-muted mb-0 fst-italic">Belum ada sub activity.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="text-center py-3 bg-white">
                            <i class="fa-solid fa-inbox text-muted opacity-30 fs-4 d-block mb-1"></i>
                            <p class="small text-muted fst-italic mb-0" style="font-size: 0.75rem;">No activities yet. Click "+ Activity" to add one.</p>
                        </div>
                    @endif
                    </div> <!-- End collapseMs -->
                </div> <!-- End milestone-card -->
                @endif
                @empty
                    @if($groupType === 'milestones')
                    <div class="text-center py-4 rounded-3 border border-2 border-dashed bg-white" style="border-color: #cbd5e1 !important;">
                        <i class="fa-solid fa-flag text-muted opacity-30 fs-3 d-block mb-2"></i>
                        <p class="small text-muted mb-2">No milestones yet.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="openAddMilestone({{ $sub->id }}, 'milestone'); document.getElementById('milestoneModalSubLabel').textContent = 'Sub Program: ' + '{{ addslashes($sub->name) }}';">
                            <i class="fa-solid fa-plus me-1"></i> Add Milestone
                        </button>
                    </div>
                    @else
                    <div class="text-center py-3 rounded-3 border border-2 border-dashed bg-white" style="border-color: #fecaca !important;">
                        <p class="small text-muted mb-2">Belum ada Key Result.</p>
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="openAddMilestone({{ $sub->id }}, 'key_result'); document.getElementById('milestoneModalSubLabel').textContent = 'Sub Program: ' + '{{ addslashes($sub->name) }}';">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Key Result
                        </button>
                        @endif
                    </div>
                    @endif
                @endforelse
                </div> <!-- End sub-body -->
                @endforeach
            </div> <!-- End collapseSub -->
        </div> <!-- End sub-card -->
        @empty
            <div class="text-center py-5 rounded-3 border border-2 border-dashed bg-white" style="border-color: #c7d2fe !important;">
                <div class="mb-3 opacity-40" style="font-size: 3rem; color: #6366f1;">
                    <i class="fa-solid fa-diagram-project"></i>
                </div>
                <p class="text-muted mb-3" style="font-size: 0.85rem;">No sub-programs yet. Start building your timeline structure.</p>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#modalAddSubProgram">
                    <i class="fa-solid fa-plus me-1"></i> Add First Sub Program
                </button>
            </div>
        @endforelse
            </div>
        </div>

        {{-- TAB: TIMELINE (AJAX) --}}
        <div class="tab-pane fade" id="timeline" role="tabpanel" aria-labelledby="timeline-tab">
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        {{-- TAB: CALENDAR (AJAX) --}}
        <div class="tab-pane fade" id="calendar" role="tabpanel" aria-labelledby="calendar-tab">
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        {{-- TAB 4: DOKUMEN --}}
        <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="dokumen-tab">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-transparent">
                <div class="card-body p-0">
                    @if($totalAttachments === 0)
                        <div class="text-center py-5 bg-white rounded-4 border">
                            <i class="fa-solid fa-folder-open text-muted opacity-25 mb-3" style="font-size: 3.5rem;"></i>
                            <p class="text-muted fw-semibold">Belum ada dokumen terlampir.</p>
                        </div>
                    @else
                        @foreach($attachmentsData as $entity)
                            @if($entity['attachments']->count() > 0)
                                @php
                                    $entityModel = null;
                                    if ($entity['attachable_type'] === 'sub_program') $entityModel = App\Models\SubProgram::find($entity['attachable_id']);
                                    if ($entity['attachable_type'] === 'milestone')   $entityModel = App\Models\Milestone::find($entity['attachable_id']);
                                    if ($entity['attachable_type'] === 'activity')    $entityModel = App\Models\Activity::find($entity['attachable_id']);
                                    $entityName = $entityModel ? $entityModel->name : 'Unknown';
                                @endphp
                                <div class="mb-4">
                                    <div class="d-flex align-items-center gap-2 mb-3 px-1">
                                        @if($entity['attachable_type'] === 'sub_program')
                                            <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.05em;">SUB PROGRAM</span>
                                        @elseif($entity['attachable_type'] === 'milestone')
                                            <span class="badge bg-info text-dark rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.05em;">MILESTONE</span>
                                        @else
                                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.05em;">ACTIVITY</span>
                                        @endif
                                        <h6 class="fw-bold text-secondary mb-0 fs-6">{{ $entityName }}</h6>
                                    </div>
                                    <div class="row g-3">
                                        @foreach($entity['attachments'] as $att)
                                        @php $tc = \App\Models\Attachment::typeColor($att->type); @endphp
                                        <div class="col-12 col-md-6 col-xl-4">
                                            <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift" style="transition: transform 0.2s, box-shadow 0.2s;">
                                                <div class="card-body p-3 d-flex flex-column">
                                                    <div class="d-flex align-items-start gap-3 mb-3">
                                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #f8fafc; border: 1px solid #e2e8f0;">
                                                            <i class="fa-solid {{ $att->icon_class }} fs-4"></i>
                                                        </div>
                                                        <div class="flex-grow-1 min-width-0">
                                                            <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.85rem;" title="{{ $att->name }}">{{ $att->name }}</h6>
                                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                                <span class="badge rounded-pill fw-semibold" style="background: {{ $tc['bg'] }}; color: {{ $tc['color'] }}; font-size: 0.6rem; border: 1px solid {{ $tc['color'] }}33;">
                                                                    {{ $att->type }}
                                                                </span>
                                                                <span class="text-muted" style="font-size: 0.65rem;">{{ $att->file_size_human }}</span>
                                                            </div>
                                                            <p class="text-muted small mb-0 flex-grow-1 text-truncate" style="font-size: 0.7rem;" title="{{ $att->original_filename }}">{{ $att->original_filename }}</p>
                                                        </div>
                                                    </div>
                                                    @if($att->description)
                                                        <p class="text-muted fst-italic mb-3 px-2 py-1 rounded-2" style="font-size: 0.7rem; background: #f1f5f9;">{{ Str::limit($att->description, 60) }}</p>
                                                    @endif
                                                    <div class="d-flex gap-2 mt-auto pt-2 border-top border-light-subtle">
                                                        <a href="{{ $att->download_url }}" class="btn btn-sm flex-grow-1 fw-semibold" style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 0.75rem;">
                                                            <i class="fa-solid fa-download me-1"></i> Download
                                                        </a>
                                                        <form action="{{ route('attachments.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?')" class="flex-shrink-0">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm px-2" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.75rem;" title="Hapus">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
            <style>
                .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important; }
            </style>
        </div>

        {{-- TAB 3: RIWAYAT --}}
        <div class="tab-pane fade" id="riwayat" role="tabpanel" aria-labelledby="riwayat-tab">
            <div class="bg-white rounded-4 shadow-sm p-4 p-md-5 border-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <h5 class="fw-bold mb-0 text-dark fs-5"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Activity Log</h5>
                    
                    {{-- Search Filters --}}
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <select id="history-user-filter" class="form-select form-select-sm" style="width: 150px;">
                            <option value="">Semua User</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" id="history-date-filter" class="form-control form-control-sm" style="width: 140px;">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <input type="text" id="history-search-filter" class="form-control" placeholder="Cari aktivitas...">
                            <span class="input-group-text bg-white border-start-0 text-muted"><i class="fa-solid fa-search"></i></span>
                        </div>
                    </div>
                </div>
                
                <div id="history-container">
                    @include('programs.partials.history_list', ['activityLogs' => $activityLogs])
                </div>
            </div>
        </div>
        {{-- TAB 6: MEMBERS --}}
        <div class="tab-pane fade" id="member" role="tabpanel" aria-labelledby="member-tab">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 px-4 d-flex rounded-4 justify-content-between align-items-center border-bottom border-light">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;">
                        <i class="fa-solid fa-users text-primary me-2"></i>Project Members
                    </h5>
                    @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fa-solid fa-user-plus me-1"></i> Add Member
                        </button>
                    @endif
                </div>
                <div class="card-body p-0" style="overflow: visible !important;">
                    <div class="table-responsive" style="overflow: visible;">
                        <table class="table table-hover align-middle mb-5">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th class="ps-4 border-0 text-muted fw-semibold small text-uppercase" style="font-size: 0.65rem;">User</th>
                                    <th class="border-0 text-muted fw-semibold small text-uppercase" style="font-size: 0.65rem;">Role in Project</th>
                                    <th class="border-0 text-muted fw-semibold small text-uppercase" style="font-size: 0.65rem;">Joined Date</th>
                                    <th class="pe-4 border-0 text-end text-muted fw-semibold small text-uppercase" style="font-size: 0.65rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($program->members as $member)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="flex-shrink-0">
                                                    @if($member->avatar)
                                                        <img src="{{ asset($member->avatar) }}" class="rounded-circle border border-2 border-white shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 0.8rem; border: 2px solid white;">
                                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">{{ $member->name }}</div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $member->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $badgeColor = match($member->pivot->role) {
                                                    'administrator' => 'bg-danger',
                                                    'manager' => 'bg-warning text-dark',
                                                    default => 'bg-info',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeColor }} rounded-pill px-3 py-1 fw-medium" style="font-size: 0.7rem;">
                                                {{ ucfirst($member->pivot->role) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $member->pivot->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="pe-4 text-end">
                                            @if(($userRole === 'administrator' || $userRole === 'manager') && $member->id != 1)
                                                <div class="d-flex justify-content-end gap-2">
                                                    {{-- Change Role --}}
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light border p-1 px-2" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                            <i class="fa-solid fa-user-shield text-muted"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                                                            <form action="{{ route('programs.members.update', [$program, $member]) }}" method="POST">
                                                                @csrf @method('PUT')
                                                                <button type="submit" name="role" value="administrator" class="dropdown-item py-2 d-flex align-items-center gap-2 {{ $member->pivot->role === 'administrator' ? 'active' : '' }}">
                                                                    <span class="p-1 bg-danger rounded-circle" style="width:8px;height:8px;"></span> Administrator
                                                                </button>
                                                                <button type="submit" name="role" value="manager" class="dropdown-item py-2 d-flex align-items-center gap-2 {{ $member->pivot->role === 'manager' ? 'active' : '' }}">
                                                                    <span class="p-1 bg-warning rounded-circle" style="width:8px;height:8px;"></span> Manager
                                                                </button>
                                                                <button type="submit" name="role" value="member" class="dropdown-item py-2 d-flex align-items-center gap-2 {{ $member->pivot->role === 'member' ? 'active' : '' }}">
                                                                    <span class="p-1 bg-info rounded-circle" style="width:8px;height:8px;"></span> Member
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    {{-- Remove --}}
                                                    <form action="{{ route('programs.members.destroy', [$program, $member]) }}" method="POST" onsubmit="return confirm('Remove member?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-light border p-1 px-2 text-danger">
                                                            <i class="fa-solid fa-user-minus"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- End Tab Content --}}

    {{-- ADD MEMBER MODAL --}}
    @if($userRole === 'administrator' || $userRole === 'manager')
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('programs.members.store', $program->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom border-light p-4">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                            <i class="fa-solid fa-user-plus text-primary me-2"></i> Add Team Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-2 text-uppercase opacity-50">Select User</label>
                            <select name="user_id" class="form-select border-0 bg-light shadow-none py-2 px-3 rounded-3" required>
                                <option value="" disabled selected>Choose a user...</option>
                                @foreach($allUsers as $u)
                                    @if(!$program->members->contains($u->id))
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-bold text-dark small mb-2 text-uppercase opacity-50">Assign Role</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="p-3 bg-light rounded-3 cursor-pointer d-flex align-items-center gap-3 transition-all role-option">
                                    <input type="radio" name="role" value="member" checked class="form-check-input mt-0">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark small">Member</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Can view projects and add attachments.</div>
                                    </div>
                                    <i class="fa-solid fa-user text-info"></i>
                                </label>
                                <label class="p-3 bg-light rounded-3 cursor-pointer d-flex align-items-center gap-3 transition-all role-option">
                                    <input type="radio" name="role" value="manager" class="form-check-input mt-0">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark small">Manager</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Can edit activities, milestones, and manage members.</div>
                                    </div>
                                    <i class="fa-solid fa-user-tie text-warning"></i>
                                </label>
                                <label class="p-3 bg-light rounded-3 cursor-pointer d-flex align-items-center gap-3 transition-all role-option">
                                    <input type="radio" name="role" value="administrator" class="form-check-input mt-0">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark small">Administrator</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Full project control, including deletion and settings.</div>
                                    </div>
                                    <i class="fa-solid fa-user-shield text-danger"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-light p-4 pt-3">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-medium text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Add to Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>{{-- End Container --}}

{{-- ============================================================
     MODALS - ADD                                                  
============================================================ --}}

{{-- ADD Sub Program --}}
<div class="modal fade" id="modalAddSubProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formAddSubProgram" action="{{ route('sub_programs.store') }}" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('formAddSubProgram', 'modalAddSubProgram', (data) => { refreshPageContent(); })">
                @csrf
                <input type="hidden" name="program_id" value="{{ $program->id }}">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #eef2ff;">
                            <i class="fa-solid fa-diagram-project" style="color: #4f46e5;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Tambah Sub Program</h5>
                            <p class="text-muted mb-0" style="font-size: 0.75rem;">Program: <strong>{{ $program->name }}</strong></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Sub Program <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Modul Backend" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">
                                Bobot <span class="text-muted fw-normal">(%)</span>
                            </label>
                            <input type="number" name="bobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Periode Tanggal</label>
                        <input type="text" id="addSubProgramDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                        <input type="hidden" name="start_date" id="addSubProgramStart">
                        <input type="hidden" name="end_date" id="addSubProgramEnd">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4"><i class="fa-solid fa-plus me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ADD Milestone --}}
<div class="modal fade" id="modalAddMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formAddMilestone" action="{{ route('milestones.store') }}" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('formAddMilestone', 'modalAddMilestone', (data) => { refreshPageContent(); })">
                @csrf
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #dbeafe;">
                            <i class="fa-solid fa-flag-checkered" style="color: #3b82f6;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalAddMilestoneLabel">Tambah Milestone</h5>
                            <p class="text-muted mb-0 small" id="milestoneModalSubLabel">Sub Program: —</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="small text-muted mb-3" id="milestoneModalTypeDesc">Menghubungkan target ke Sub Program tertentu.</p>
                    <input type="hidden" name="sub_program_id" id="milestoneSubProgramId" value="">
                    <input type="hidden" name="type" id="addMilestoneType" value="milestone">
                    <div class="row g-2 mb-3">
                        <div class="col-9" id="addMilestoneNameContainer">
                            <label class="form-label fw-semibold small" id="milestoneModalNameLabel">Nama Milestone <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Desain Database" required>
                        </div>
                        <div class="col-3" id="addMilestoneBobotContainer">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div id="addMilestoneExtraFields">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Periode Tanggal</label>
                            <input type="text" id="addMilestoneDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="addMilestoneStart">
                            <input type="hidden" name="end_date" id="addMilestoneEnd">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4"><i class="fa-solid fa-plus me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ADD Activity --}}
<div class="modal fade" id="modalAddActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formAddActivity" action="{{ route('activities.store') }}" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('formAddActivity', 'modalAddActivity', (data) => { refreshPageContent(); })">
                @csrf
                <input type="hidden" name="milestone_id" id="activityMilestoneId" value="">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #d1fae5;">
                            <i class="fa-solid fa-list-check" style="color: #059669;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Tambah Activity</h5>
                            <p class="text-muted mb-0 small" id="activityMilestoneLabel">Milestone: —</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Desain ERD" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Periode Tanggal <span class="text-danger">*</span></label>
                            <input type="text" id="addActivityDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="addActivityStart" required>
                            <input type="hidden" name="end_date" id="addActivityEnd" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Progress (%) <span class="text-danger">*</span></label>
                            <input type="number" name="progress" class="form-control form-control-sm" value="0" min="0" max="100" required>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Status Manual <span class="text-danger">*</span></label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="Draft" selected>Draft</option>
                                <option value="To Do">To Do</option>
                                <option value="On Progress">On Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-building me-1 opacity-50"></i> UIC</label>
                            <input type="text" name="uic" class="form-control form-control-sm" placeholder="e.g. IT Division">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-user me-1 opacity-50"></i> PIC</label>
                            <input type="text" name="pic" class="form-control form-control-sm" placeholder="e.g. Budi Santoso">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-4"><i class="fa-solid fa-plus me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================
     MODALS - EDIT                                                 
============================================================ --}}

{{-- EDIT Program --}}
<div class="modal fade" id="modalEditProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editProgramForm" action="" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('editProgramForm', 'modalEditProgram', (data) => { refreshPageContent(); })">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #eef2ff;">
                            <i class="fa-solid fa-pen-to-square" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0">Edit Program</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Prefix <span class="text-muted fw-normal">(e.g. 1.1)</span></label>
                            <input type="text" name="prefix" id="editProgramPrefix" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold small">Tema <span class="text-muted fw-normal">(e.g. Transformation)</span></label>
                            <input type="text" name="theme" id="editProgramTheme" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Inisiatif Program <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editProgramName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" id="editProgramDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Periode Tanggal</label>
                        <input type="text" id="editProgramDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                        <input type="hidden" name="start_date" id="editProgramStart">
                        <input type="hidden" name="end_date" id="editProgramEnd">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT Sub Program --}}
<div class="modal fade" id="modalEditSubProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editSubProgramForm" action="" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('editSubProgramForm', 'modalEditSubProgram', (data) => { refreshPageContent(); })">
                @csrf @method('PUT')
                <input type="hidden" name="program_id" id="editSubProgramProgramId">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #dbeafe;">
                            <i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit Sub Program</h5>
                            <p class="text-muted mb-0 small" id="editSubProgramLabel"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Sub Program <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editSubProgramName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" id="editSubProgramBobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" id="editSubProgramDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Periode Tanggal</label>
                        <input type="text" id="editSubProgramDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                        <input type="hidden" name="start_date" id="editSubProgramStart">
                        <input type="hidden" name="end_date" id="editSubProgramEnd">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT Milestone --}}
<div class="modal fade" id="modalEditMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editMilestoneForm" action="" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('editMilestoneForm', 'modalEditMilestone', (data) => { refreshPageContent(); })">
                @csrf @method('PUT')
                <input type="hidden" name="sub_program_id" id="editMilestoneSubProgramId">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #ede9fe;">
                            <i class="fa-solid fa-pen-to-square" style="color: #7c3aed;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit Milestone</h5>
                            <p class="text-muted mb-0 small" id="editMilestoneLabel"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-9" id="editMilestoneNameContainer">
                            <label class="form-label fw-semibold small" id="editMilestoneModalNameLabel">Nama Milestone <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editMilestoneName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3" id="editMilestoneBobotContainer">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" id="editMilestoneBobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div id="editMilestoneExtraFields">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" id="editMilestoneDesc" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Periode Tanggal</label>
                            <input type="text" id="editMilestoneDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="editMilestoneStart">
                            <input type="hidden" name="end_date" id="editMilestoneEnd">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Duplicate --}}
<div class="modal fade" id="modalDuplicate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-copy me-2"></i>Duplikasi <span id="duplicateEntityTitle"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDuplicate" onsubmit="submitDuplicateForm(event)">
                @csrf
                <input type="hidden" name="type" id="duplicateType">
                <input type="hidden" name="id" id="duplicateId">
                <div class="modal-body p-4">
                    <p class="mb-3">Anda akan menduplikasi <strong><span id="duplicateEntityName"></span></strong>.</p>
                    
                    <div id="duplicateOptionsSub" class="d-none">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="with_milestones" id="dupWithMilestones" value="1" checked>
                            <label class="form-check-label" for="dupWithMilestones">
                                Sertakan Milestone
                            </label>
                        </div>
                        <div class="form-check mb-2" id="dupWithActivitiesContainer">
                            <input class="form-check-input" type="checkbox" name="with_activities_sub" id="dupWithActivitiesSub" value="1" checked>
                            <label class="form-check-label" for="dupWithActivitiesSub">
                                Sertakan Activity
                            </label>
                        </div>
                        <div class="form-check mb-2" id="dupWithSubActivitiesContainerSub">
                            <input class="form-check-input" type="checkbox" name="with_sub_activities_sub" id="dupWithSubActivitiesSub" value="1" checked>
                            <label class="form-check-label" for="dupWithSubActivitiesSub">
                                Sertakan Sub Activity
                            </label>
                        </div>
                    </div>

                    <div id="duplicateOptionsMs" class="d-none">
                        <div class="form-check mb-2" id="dupWithActivitiesContainerMs">
                            <input class="form-check-input" type="checkbox" name="with_activities_ms" id="dupWithActivitiesMs" value="1" checked>
                            <label class="form-check-label" for="dupWithActivitiesMs">
                                Sertakan Activity
                            </label>
                        </div>
                        <div class="form-check mb-2" id="dupWithSubActivitiesContainerMs">
                            <input class="form-check-input" type="checkbox" name="with_sub_activities_ms" id="dupWithSubActivitiesMs" value="1" checked>
                            <label class="form-check-label" for="dupWithSubActivitiesMs">
                                Sertakan Sub Activity
                            </label>
                        </div>
                    </div>

                    <div id="duplicateOptionsAct" class="d-none">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="with_sub_activities_act" id="dupWithSubActivitiesAct" value="1" checked>
                            <label class="form-check-label" for="dupWithSubActivitiesAct">
                                Sertakan Sub Activity
                            </label>
                        </div>
                        <p class="text-muted small">Activity akan diduplikasi langsung ke milestone yang sama.</p>
                    </div>

                    <div id="duplicateOptionsSubAct" class="d-none">
                        <p class="text-muted small">Sub Activity akan diduplikasi langsung ke activity yang sama.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Duplikasi Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT Activity --}}
<div class="modal fade" id="modalEditActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editActivityForm" action="" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('editActivityForm', 'modalEditActivity', (data) => { refreshPageContent(); })">
                @csrf @method('PUT')
                <input type="hidden" name="milestone_id" id="editActivityMilestoneIdHidden">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fef9c3;">
                            <i class="fa-solid fa-pen-to-square" style="color: #ca8a04;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit Activity</h5>
                            <p class="text-muted mb-0 small" id="editActivityLabel"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editActivityName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" id="editActivityDesc" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Periode Tanggal <span class="text-danger">*</span></label>
                            <input type="text" id="editActivityDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="editActivityStart">
                            <input type="hidden" name="end_date" id="editActivityEnd">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Progress (%) <span class="text-danger">*</span></label>
                            <input type="number" name="progress" id="editActivityProgress" class="form-control form-control-sm" min="0" max="100" required>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Status Manual <span class="text-danger">*</span></label>
                            <select name="status" id="editActivityStatus" class="form-select form-select-sm" required>
                                <option value="Draft">Draft</option>
                                <option value="To Do">To Do</option>
                                <option value="On Progress">On Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-building me-1 opacity-50"></i> UIC</label>
                            <input type="text" name="uic" id="editActivityUic" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-user me-1 opacity-50"></i> PIC</label>
                            <input type="text" name="pic" id="editActivityPic" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold px-4 text-dark"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ADD Sub Activity --}}
<div class="modal fade" id="modalAddSubActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="addSubActivityForm" action="{{ route('sub_activities.store') }}" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('addSubActivityForm', 'modalAddSubActivity', (data) => { refreshPageContent(); })">
                @csrf
                <input type="hidden" name="activity_id" id="activity_id_for_sub_act">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #d1fae5;">
                            <i class="fa-solid fa-plus text-success"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Add Sub Activity</h5>
                            <p class="text-muted mb-0 small" id="subActivityModalActLabel"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Sub Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Buat Relasi Tabel" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Periode Tanggal <span class="text-danger">*</span></label>
                            <input type="text" id="addSubActivityDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="addSubActivityStart">
                            <input type="hidden" name="end_date" id="addSubActivityEnd">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Progress (%) <span class="text-danger">*</span></label>
                            <input type="number" name="progress" class="form-control form-control-sm" value="0" min="0" max="100" required>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Status Manual <span class="text-danger">*</span></label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="Draft" selected>Draft</option>
                                <option value="To Do">To Do</option>
                                <option value="On Progress">On Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-building me-1 opacity-50"></i> UIC</label>
                            <input type="text" name="uic" class="form-control form-control-sm" placeholder="e.g. IT Dept">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-user me-1 opacity-50"></i> PIC</label>
                            <input type="text" name="pic" class="form-control form-control-sm" placeholder="e.g. John Doe">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-4"><i class="fa-solid fa-check me-1"></i> Simpan Sub Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT Sub Activity --}}
<div class="modal fade" id="modalEditSubActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editSubActivityForm" action="" method="POST" onsubmit="event.preventDefault(); submitAjaxForm('editSubActivityForm', 'modalEditSubActivity', (data) => { refreshPageContent(); })">
                @csrf @method('PUT')
                <input type="hidden" name="activity_id" id="editSubActivityActIdHidden">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fef9c3;">
                            <i class="fa-solid fa-pen-to-square" style="color: #ca8a04;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit Sub Activity</h5>
                            <p class="text-muted mb-0 small" id="editSubActivityLabel"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nama Sub Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editSubActivityName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" id="editSubActivityDesc" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Periode Tanggal <span class="text-danger">*</span></label>
                            <input type="text" id="editSubActivityDateRange" class="form-control form-control-sm flatpickr-range" placeholder="Pilih tanggal mulai — selesai" readonly>
                            <input type="hidden" name="start_date" id="editSubActivityStart">
                            <input type="hidden" name="end_date" id="editSubActivityEnd">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Progress (%) <span class="text-danger">*</span></label>
                            <input type="number" name="progress" id="editSubActivityProgress" class="form-control form-control-sm" min="0" max="100" required>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Status Manual <span class="text-danger">*</span></label>
                            <select name="status" id="editSubActivityStatus" class="form-select form-select-sm" required>
                                <option value="Draft">Draft</option>
                                <option value="To Do">To Do</option>
                                <option value="On Progress">On Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-building me-1 opacity-50"></i> UIC</label>
                            <input type="text" name="uic" id="editSubActivityUic" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small"><i class="fa-solid fa-user me-1 opacity-50"></i> PIC</label>
                            <input type="text" name="pic" id="editSubActivityPic" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold px-4 text-dark"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .border-dashed { border-style: dashed !important; }
    /* Fix SweetAlert2 breaking fixed layout */
    body.swal2-shown {
        overflow: hidden !important;
        padding-right: 0 !important;
    }
    .swal2-container {
        z-index: 2000 !important;
    }
</style>

@push('scripts')
<script>
    // ---- ADD helpers ----
    function openAddMilestone(subId, type = 'milestone') {
        document.getElementById('milestoneSubProgramId').value = subId;
        document.getElementById('addMilestoneType').value = type;
        
        if (type === 'key_result') {
            document.getElementById('modalAddMilestoneLabel').textContent = 'Tambah Key Result';
            document.getElementById('milestoneModalNameLabel').innerHTML = 'Nama Key Result <span class="text-danger">*</span>';
            document.getElementById('milestoneModalTypeDesc').textContent = 'Hasil kunci terukur yang mendukung pencapaian sub program.';
            document.getElementById('addMilestoneExtraFields').classList.remove('d-none');
            document.getElementById('addMilestoneBobotContainer').classList.remove('d-none');
            document.getElementById('addMilestoneNameContainer').className = 'col-9';
        } else if (type === 'divider') {
            document.getElementById('modalAddMilestoneLabel').textContent = 'Tambah Section Divider';
            document.getElementById('milestoneModalNameLabel').innerHTML = 'Nama Section Divider <span class="text-danger">*</span>';
            document.getElementById('milestoneModalTypeDesc').textContent = 'Garis pembatas untuk mengelompokkan milestone dan mereset penomoran.';
            document.getElementById('addMilestoneExtraFields').classList.add('d-none');
            document.getElementById('addMilestoneBobotContainer').classList.add('d-none');
            document.getElementById('addMilestoneNameContainer').className = 'col-12';
        } else {
            document.getElementById('modalAddMilestoneLabel').textContent = 'Tambah Milestone';
            document.getElementById('milestoneModalNameLabel').innerHTML = 'Nama Milestone <span class="text-danger">*</span>';
            document.getElementById('milestoneModalTypeDesc').textContent = 'Menghubungkan target ke Sub Program tertentu.';
            document.getElementById('addMilestoneExtraFields').classList.remove('d-none');
            document.getElementById('addMilestoneBobotContainer').classList.remove('d-none');
            document.getElementById('addMilestoneNameContainer').className = 'col-9';
        }
    }

    function setMilestoneSubProgram(subId, subName) {
        document.getElementById('milestoneSubProgramId').value = subId;
        const subLabel = document.getElementById('milestoneModalSubLabel') || document.getElementById('milestoneSubLabel');
        if (subLabel) subLabel.textContent = 'Sub Program: ' + subName;
    }
    function setActivityMilestone(msId, msName) {
        document.getElementById('activityMilestoneId').value = msId;
        document.getElementById('activityMilestoneLabel').textContent = 'Milestone: ' + msName;
    }
    
    function setSubActivityParent(actId, actName) {
        document.getElementById('activity_id_for_sub_act').value = actId;
        document.getElementById('subActivityModalActLabel').textContent = 'Activity: ' + actName;
    }


    // ---- Date Range Picker Helpers ----
    const _fpInstances = {};
    function initDateRangePickers() {
        const configs = [
            { el: 'addSubProgramDateRange',    startId: 'addSubProgramStart',    endId: 'addSubProgramEnd' },
            { el: 'addMilestoneDateRange',      startId: 'addMilestoneStart',      endId: 'addMilestoneEnd' },
            { el: 'addActivityDateRange',       startId: 'addActivityStart',       endId: 'addActivityEnd' },
            { el: 'addSubActivityDateRange',    startId: 'addSubActivityStart',    endId: 'addSubActivityEnd' },
            { el: 'editProgramDateRange',       startId: 'editProgramStart',       endId: 'editProgramEnd' },
            { el: 'editSubProgramDateRange',    startId: 'editSubProgramStart',    endId: 'editSubProgramEnd' },
            { el: 'editMilestoneDateRange',     startId: 'editMilestoneStart',     endId: 'editMilestoneEnd' },
            { el: 'editActivityDateRange',      startId: 'editActivityStart',      endId: 'editActivityEnd' },
            { el: 'editSubActivityDateRange',   startId: 'editSubActivityStart',   endId: 'editSubActivityEnd' },
        ];
        configs.forEach(({ el, startId, endId }) => {
            const input = document.getElementById(el);
            if (!input) return;
            _fpInstances[el] = flatpickr(input, {
                mode: 'range',
                dateFormat: 'd M Y',
                locale: { firstDayOfWeek: 1 },
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        const fmt = d => d.toISOString().split('T')[0];
                        document.getElementById(startId).value = fmt(selectedDates[0]);
                        document.getElementById(endId).value   = fmt(selectedDates[1]);
                    } else {
                        document.getElementById(startId).value = '';
                        document.getElementById(endId).value   = '';
                    }
                }
            });
        });
    }

    function setRangePicker(pickerId, start, end) {
        const fp = _fpInstances[pickerId];
        if (!fp) return;
        if (start && end) {
            fp.setDate([start, end], false);
        } else {
            fp.clear();
        }
    }

    // ---- EDIT Program ----
    function openEditProgram(id, prefix, theme, name, desc, start, end) {
        document.getElementById('editProgramForm').action = '/programs/' + id;
        document.getElementById('editProgramPrefix').value = prefix;
        document.getElementById('editProgramTheme').value = theme;
        document.getElementById('editProgramName').value = name;
        document.getElementById('editProgramDesc').value = desc;
        document.getElementById('editProgramStart').value = start;
        document.getElementById('editProgramEnd').value = end;
        setRangePicker('editProgramDateRange', start, end);
    }

    // ---- EDIT Sub Program ----
    function openEditSubProgram(id, programId, name, bobot, desc, start, end) {
        document.getElementById('editSubProgramForm').action = '/sub_programs/' + id;
        document.getElementById('editSubProgramLabel').textContent = 'Editing: ' + name;
        document.getElementById('editSubProgramProgramId').value = programId;
        document.getElementById('editSubProgramName').value = name;
        document.getElementById('editSubProgramBobot').value = bobot || '';
        document.getElementById('editSubProgramDesc').value = desc;
        document.getElementById('editSubProgramStart').value = start;
        document.getElementById('editSubProgramEnd').value = end;
        setRangePicker('editSubProgramDateRange', start, end);
    }

    // ---- EDIT Milestone ----
    function openEditMilestone(id, subProgramId, name, bobot, desc, start, end, type = 'milestone') {
        document.getElementById('editMilestoneForm').action = '/milestones/' + id;
        document.getElementById('editMilestoneLabel').textContent = 'Editing: ' + name;
        document.getElementById('editMilestoneSubProgramId').value = subProgramId;
        document.getElementById('editMilestoneName').value = name;
        document.getElementById('editMilestoneBobot').value = bobot || '';
        document.getElementById('editMilestoneDesc').value = desc;
        document.getElementById('editMilestoneStart').value = start;
        document.getElementById('editMilestoneEnd').value = end;
        setRangePicker('editMilestoneDateRange', start, end);
        
        if (type === 'divider') {
            document.getElementById('editMilestoneModalNameLabel').innerHTML = 'Nama Section Divider <span class="text-danger">*</span>';
            document.getElementById('editMilestoneExtraFields').classList.add('d-none');
            document.getElementById('editMilestoneBobotContainer').classList.add('d-none');
            document.getElementById('editMilestoneNameContainer').className = 'col-12';
        } else {
            document.getElementById('editMilestoneModalNameLabel').innerHTML = 'Nama Milestone <span class="text-danger">*</span>';
            document.getElementById('editMilestoneExtraFields').classList.remove('d-none');
            document.getElementById('editMilestoneBobotContainer').classList.remove('d-none');
            document.getElementById('editMilestoneNameContainer').className = 'col-9';
        }
    }

    // ---- EDIT Activity ----
    function openEditActivity(id, milestoneId, name, desc, start, end, progress, status, uic, pic) {
        document.getElementById('editActivityForm').action = '/activities/' + id;
        document.getElementById('editActivityLabel').textContent = 'Editing: ' + name;
        document.getElementById('editActivityMilestoneIdHidden').value = milestoneId;
        document.getElementById('editActivityName').value = name;
        document.getElementById('editActivityDesc').value = desc;
        document.getElementById('editActivityStart').value = start;
        document.getElementById('editActivityEnd').value = end;
        setRangePicker('editActivityDateRange', start, end);
        document.getElementById('editActivityProgress').value = progress;
        document.getElementById('editActivityStatus').value = status;
        document.getElementById('editActivityUic').value = uic;
        document.getElementById('editActivityPic').value = pic;
    }

    // ---- EDIT Sub Activity ----
    function openEditSubActivity(id, actId, name, desc, start, end, progress, status, uic, pic) {
        document.getElementById('editSubActivityForm').action = '/sub_activities/' + id;
        document.getElementById('editSubActivityLabel').textContent = 'Editing: ' + name;
        document.getElementById('editSubActivityActIdHidden').value = actId;
        document.getElementById('editSubActivityName').value = name;
        document.getElementById('editSubActivityDesc').value = desc;
        document.getElementById('editSubActivityStart').value = start;
        document.getElementById('editSubActivityEnd').value = end;
        setRangePicker('editSubActivityDateRange', start, end);
        document.getElementById('editSubActivityProgress').value = progress;
        document.getElementById('editSubActivityStatus').value = status;
        document.getElementById('editSubActivityUic').value = uic;
        document.getElementById('editSubActivityPic').value = pic;
    }


    // ---- AJAX CRUD Helpers ----
    async function submitAjaxForm(formId, modalId, onSuccess) {
        const form = document.getElementById(formId);
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Simpan...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                if (modal) modal.hide();
                form.reset();
                if (onSuccess) onSuccess(result.data);
                // Optional: show toast/notification
                console.log(result.message);
                if (typeof recalculateHierarchyNumbering === 'function') recalculateHierarchyNumbering();
            } else {
                alert(result.message || 'Terjadi kesalahan saat menyimpan data.');
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            alert('Gagal menghubungi server.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    }

    async function deleteHierarchyItem(url, confirmMsg, onSuccess) {
        if (!confirm(confirmMsg)) return;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'DELETE'
                }
            });

            const result = await response.json();

            if (result.success) {
                if (onSuccess) onSuccess();
                if (typeof recalculateHierarchyNumbering === 'function') recalculateHierarchyNumbering();
            } else {
                alert(result.message || 'Gagal menghapus data.');
            }
        } catch (error) {
            console.error('AJAX Delete Error:', error);
            alert('Gagal menghubungi server.');
        }
    }

    async function refreshPageContent() {
        // Collect currently expanded accordion IDs
        const expandedIds = Array.from(document.querySelectorAll('.hierarchy-collapse.show'))
            .map(el => el.id);

        try {
            const response = await fetch(window.location.href);
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Refresh Header Area
            const newHeader = doc.getElementById('program-header-content');
            const oldHeader = document.getElementById('program-header-content');
            if (newHeader && oldHeader) {
                oldHeader.innerHTML = newHeader.innerHTML;
            }

            // Refresh Hierarchy Container
            const newContainer = doc.getElementById('sub-programs-container');
            const oldContainer = document.getElementById('sub-programs-container');
            
            if (newContainer && oldContainer) {
                oldContainer.innerHTML = newContainer.innerHTML;
                
                // Update attachments data
                const newAttachScript = doc.getElementById('attachments-data-script');
                if (newAttachScript) {
                    try {
                        _allAttachments = JSON.parse(newAttachScript.textContent);
                    } catch (e) {
                        console.error('JSON Parse Error for Attachments:', e);
                    }
                }
                
                // Restore expanded state
                expandedIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.classList.add('show');
                        const trigger = document.querySelector(`[data-bs-target="#${id}"]`);
                        if (trigger) trigger.setAttribute('aria-expanded', 'true');
                    }
                });

                if (typeof initSortable === 'function') initSortable();
                if (typeof recalculateHierarchyNumbering === 'function') recalculateHierarchyNumbering();
                
                const searchInput = document.getElementById('hierarchySearch');
                if (searchInput) {
                    searchInput.dispatchEvent(new Event('input'));
                }
            }
        } catch (error) {
            console.error('Refresh Content Error:', error);
        }
    }

    // ---- Init popovers (status chips) using DOM content divs ----
    document.addEventListener('DOMContentLoaded', function () {
        const statusChips = ['upcoming', 'active', 'delayed', 'completed'];
        const popoverInstances = [];

        statusChips.forEach(function(status) {
            var chip    = document.getElementById('chip-' + status);
            var content = document.getElementById('chip-' + status + '-content');
            if (!chip || !content) return;

            var popover = new bootstrap.Popover(chip, {
                html: true,
                trigger: 'click', 
                sanitize: false,
                title: chip.querySelector('i').outerHTML + ' ' + chip.querySelector('span').textContent.trim(),
                content: content.innerHTML,
                placement: 'bottom',
                container: 'body',
                offset: [0, 5],
                customClass: 'status-popover'
            });

            popoverInstances.push(popover);

            // Close others when one is clicked
            chip.addEventListener('click', function() {
                popoverInstances.forEach(instance => {
                    if (instance !== popover) {
                        instance.hide();
                    }
                });
            });
        });

        // Close all popovers when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.chip-status') && !e.target.closest('.status-popover')) {
                popoverInstances.forEach(instance => instance.hide());
            }
        });
    });

    // ---- History AJAX Search & Pagination ----
    document.addEventListener('DOMContentLoaded', function() {
        const historyContainer = document.getElementById('history-container');
        const userFilter       = document.getElementById('history-user-filter');
        const dateFilter       = document.getElementById('history-date-filter');
        const searchFilter     = document.getElementById('history-search-filter');

        if (!historyContainer) return;

        let historyTimeout;

        function fetchHistory(page = 1) {
            const uId = userFilter.value;
            const date = dateFilter.value;
            const search = searchFilter.value;

            // Visual feedback
            historyContainer.style.opacity = '0.5';
            historyContainer.style.pointerEvents = 'none';

            const url = `{{ route('programs.history', $program->id) }}?page=${page}&user_id=${uId}&date=${date}&search=${encodeURIComponent(search)}`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                historyContainer.innerHTML = html;
                historyContainer.style.opacity = '1';
                historyContainer.style.pointerEvents = 'auto';
                
                // Re-bind pagination links
                bindPagination();
            })
            .catch(err => {
                console.error('History Fetch Error:', err);
                historyContainer.style.opacity = '1';
                historyContainer.style.pointerEvents = 'auto';
            });
        }

        function bindPagination() {
            const paginationEl = historyContainer.querySelector('.ajax-pagination');
            if (!paginationEl) return;

            const links = paginationEl.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const urlObj = new URL(this.href);
                    const page = urlObj.searchParams.get('page');
                    fetchHistory(page);
                    
                    // Scroll to top of tab section
                    document.getElementById('riwayat-tab').scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        }

        if (userFilter) {
            userFilter.addEventListener('change', () => fetchHistory(1));
        }
        if (dateFilter) {
            dateFilter.addEventListener('change', () => fetchHistory(1));
        }
        if (searchFilter) {
            searchFilter.addEventListener('input', () => {
                clearTimeout(historyTimeout);
                historyTimeout = setTimeout(() => fetchHistory(1), 500);
            });
        }

        // Initial bind
        bindPagination();
    });
</script>
@endpush

{{-- ===== ATTACHMENT LIST MODAL ===== --}}
<div class="modal fade" id="modalAttachments" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fef3c7;">
                        <i class="fa-solid fa-paperclip" style="color: #d97706;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em;">Lampiran</p>
                        <h5 class="fw-bold text-dark mb-0 fs-6" id="attachmentModalTitle">—</h5>
                    </div>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-sm fw-semibold px-3"
                            style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e;"
                            onclick="openUploadAttachmentModal()">
                        <i class="fa-solid fa-upload me-1"></i> Upload
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body px-4 py-3" id="attachmentListBody">
                <p class="text-muted text-center fst-italic py-4" style="font-size: 0.82rem;">Belum ada lampiran.</p>
            </div>
        </div>
    </div>
</div>

{{-- ===== ATTACHMENT UPLOAD MODAL ===== --}}
<div class="modal fade" id="modalUploadAttachment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="uploadAttachmentForm" action="{{ route('attachments.store') }}" method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault(); submitAjaxForm('uploadAttachmentForm', 'modalUploadAttachment', async (data) => { await refreshPageContent(); openAttachmentModal(_attachCurrentType, _attachCurrentId, _attachCurrentName); })">
                @csrf
                <input type="hidden" name="attachable_type" id="uploadAttachableType">
                <input type="hidden" name="attachable_id"   id="uploadAttachableId">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fef3c7;">
                            <i class="fa-solid fa-upload" style="color: #d97706;"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Upload Lampiran</p>
                            <h5 class="fw-bold mb-0 fs-6" id="uploadAttachmentLabel">—</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if($errors->any())
                    <div class="alert alert-danger py-2 mb-3" style="font-size: 0.8rem;">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['RAB' => ['#dbeafe','#1d4ed8','fa-calculator'], 'Evidence' => ['#d1fae5','#065f46','fa-camera'], 'Paparan' => ['#fef3c7','#92400e','fa-presentation-screen'], 'Other' => ['#f1f5f9','#475569','fa-file']] as $t => $tc)
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border cursor-pointer att-type-label"
                                   style="background: {{ $tc[0] }}; border-color: {{ $tc[1] }}44 !important; color: {{ $tc[1] }}; font-size: 0.78rem; cursor: pointer;">
                                <input type="radio" name="type" value="{{ $t }}" class="d-none att-type-radio">
                                <i class="fa-solid {{ $tc[2] }}"></i> {{ $t }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul / Label <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. RAB Final Q1 2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="uploadFileInput" class="form-control form-control-sm" required
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.gif,.zip,.rar,.txt,.csv">
                        <div class="form-text" style="font-size: 0.68rem;">PDF, Word, Excel, PPT, Gambar, ZIP · Maks 20 MB</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm fw-semibold px-4" style="background: #d97706; color: white;">
                        <i class="fa-solid fa-upload me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function initSortable() {
        // 1. Sortable Sub Programs
        const subContainer = document.getElementById('sub-programs-container');
        if (subContainer) {
            new Sortable(subContainer, {
                animation: 150,
                handle: '.sub-drag-handle',
                ghostClass: 'bg-primary-subtle',
                onEnd: function (evt) {
                    const order = Array.from(subContainer.querySelectorAll('.sub-card')).map(el => el.dataset.id);
                    updateHierarchyOrder('sub_program', order);
                    recalculateHierarchyNumbering();
                }
            });
        }

        // 2. Sortable Milestones
        document.querySelectorAll('.milestones-container').forEach(container => {
            new Sortable(container, {
                animation: 150,
                group: 'milestones',
                handle: '.ms-drag-handle',
                ghostClass: 'bg-primary-subtle',
                onEnd: function (evt) {
                    const toContainer = evt.to;
                    const order = Array.from(toContainer.querySelectorAll('.milestone-card')).map(el => el.dataset.id);
                    const newParentId = toContainer.dataset.subId;
                    updateHierarchyOrder('milestone', order, newParentId);
                    recalculateHierarchyNumbering();
                }
            });
        });

        // 2.5. Sortable Key Results
        document.querySelectorAll('.key-results-container').forEach(container => {
            new Sortable(container, {
                animation: 150,
                group: 'key_results',
                handle: '.ms-drag-handle',
                ghostClass: 'bg-danger-subtle',
                onEnd: function (evt) {
                    const toContainer = evt.to;
                    const order = Array.from(toContainer.querySelectorAll('.milestone-card')).map(el => el.dataset.id);
                    const newParentId = toContainer.dataset.subId;
                    updateHierarchyOrder('key_result', order, newParentId);
                    recalculateHierarchyNumbering();
                }
            });
        });

        // 3. Sortable Activities
        document.querySelectorAll('.activities-container').forEach(container => {
            new Sortable(container, {
                animation: 150,
                group: 'activities',
                handle: '.act-drag-handle',
                ghostClass: 'bg-primary-subtle',
                onEnd: function (evt) {
                    const toContainer = evt.to;
                    const order = Array.from(toContainer.querySelectorAll('.activity-item')).map(el => el.dataset.id);
                    const newParentId = toContainer.dataset.msId;
                    updateHierarchyOrder('activity', order, newParentId);
                    recalculateHierarchyNumbering();
                }
            });
        });

        // 4. Sortable Sub Activities
        document.querySelectorAll('.sub-activities-container').forEach(container => {
            new Sortable(container, {
                animation: 150,
                group: 'sub_activities',
                handle: '.sub-act-drag-handle',
                ghostClass: 'bg-primary-subtle',
                onEnd: function (evt) {
                    const toContainer = evt.to;
                    const order = Array.from(toContainer.querySelectorAll('.sub-activity-item')).map(el => el.dataset.id);
                    const newParentId = toContainer.dataset.actId;
                    updateHierarchyOrder('sub_activity', order, newParentId);
                    recalculateHierarchyNumbering();
                }
            });
        });
    }

    // ---- Inline Date Range Pickers for Activity / Sub Activity rows ----
    function initInlineDatePickers() {
        document.querySelectorAll('.inline-date-range').forEach(function(el) {
            if (el._fpInline) return; // Already initialised

            // Create a hidden real input Flatpickr attaches to
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'text';
            hiddenInput.style.cssText = 'position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;';
            el.appendChild(hiddenInput);

            var fp = flatpickr(hiddenInput, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: [el.dataset.start || null, el.dataset.end || null].filter(Boolean),
                onChange: function(selectedDates) {
                    if (selectedDates.length !== 2) return;

                    var fmt = function(d) { return d.toISOString().split('T')[0]; };
                    var start = fmt(selectedDates[0]);
                    var end   = fmt(selectedDates[1]);

                    // Visual feedback
                    var displayEl = el.querySelector('.act-date-display, .subact-date-display');
                    var originalHtml = displayEl ? displayEl.innerHTML : '';
                    if (displayEl) displayEl.innerHTML = '<span class="text-primary fst-italic">Menyimpan...</span>';

                    var type    = el.dataset.type;  // 'activity' or 'sub_activity'
                    var rawId   = (type === 'activity' ? 'act_' : 'subact_') + el.dataset.id;
                    var csrfEl  = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfEl) return;

                    fetch('/api/projects/gantt/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfEl.content,
                            'Accept':       'application/json'
                        },
                        body: JSON.stringify({ id: rawId, start: start, end: end, progress: null })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            // Update display
                            el.dataset.start = start;
                            el.dataset.end   = end;
                            var opts = { day: '2-digit', month: 'short', year: '2-digit' };
                            var s = new Date(start + 'T00:00:00').toLocaleDateString('id-ID', opts);
                            var e = new Date(end   + 'T00:00:00').toLocaleDateString('id-ID', opts);
                            if (displayEl) displayEl.innerHTML = s + ' \u2192 ' + e;
                            // Brief green flash
                            el.style.borderColor = '#4ade80';
                            el.style.background  = '#f0fdf4';
                            setTimeout(function() {
                                el.style.borderColor = '';
                                el.style.background  = 'transparent';
                            }, 1200);
                        } else {
                            if (displayEl) displayEl.innerHTML = originalHtml;
                            alert('Gagal menyimpan tanggal.');
                        }
                    })
                    .catch(function() {
                        if (displayEl) displayEl.innerHTML = originalHtml;
                        alert('Koneksi gagal.');
                    });
                }
            });

            el._fpInline = fp;

            // Open picker on click anywhere in the span
            el.addEventListener('click', function() { fp.open(); });
        });
    }

    document.addEventListener('DOMContentLoaded', () => { initSortable(); initDateRangePickers(); initInlineDatePickers(); });

    function openDuplicateModal(type, id, name) {
        document.getElementById('duplicateType').value = type;
        document.getElementById('duplicateId').value = id;
        document.getElementById('duplicateEntityName').textContent = name;
        
        // Setup title
        let title = '';
        if (type === 'sub_program') title = 'Sub Program';
        if (type === 'milestone')   title = 'Milestone';
        if (type === 'activity')    title = 'Activity';
        if (type === 'sub_activity') title = 'Sub Activity';
        document.getElementById('duplicateEntityTitle').textContent = title;

        // Toggle options and disable hidden inputs
        const optionsSub = document.getElementById('duplicateOptionsSub');
        const optionsMs  = document.getElementById('duplicateOptionsMs');
        const optionsAct = document.getElementById('duplicateOptionsAct');
        const optionsSubAct = document.getElementById('duplicateOptionsSubAct');

        optionsSub.classList.add('d-none');
        optionsMs.classList.add('d-none');
        optionsAct.classList.add('d-none');
        optionsSubAct.classList.add('d-none');

        // Disable all inputs initially
        optionsSub.querySelectorAll('input').forEach(i => i.disabled = true);
        optionsMs.querySelectorAll('input').forEach(i => i.disabled = true);
        optionsAct.querySelectorAll('input').forEach(i => i.disabled = true);

        if (type === 'sub_program') {
            optionsSub.classList.remove('d-none');
            optionsSub.querySelectorAll('input').forEach(i => i.disabled = false);
            
            // Logic for auto-toggling activity based on milestone
            const msCheck = document.getElementById('dupWithMilestones');
            const actContainer = document.getElementById('dupWithActivitiesContainer');
            const subActCheck = document.getElementById('dupWithActivitiesSub');
            const subActContainer = document.getElementById('dupWithSubActivitiesContainerSub');

            msCheck.onchange = () => {
                actContainer.style.opacity = msCheck.checked ? '1' : '0.5';
                document.getElementById('dupWithActivitiesSub').disabled = !msCheck.checked;
                // trigger cascade
                if (!msCheck.checked) {
                    document.getElementById('dupWithActivitiesSub').checked = false;
                }
                subActCheck.onchange();
            };
            subActCheck.onchange = () => {
                const actEnabledAndChecked = !document.getElementById('dupWithActivitiesSub').disabled && document.getElementById('dupWithActivitiesSub').checked;
                subActContainer.style.opacity = actEnabledAndChecked ? '1' : '0.5';
                document.getElementById('dupWithSubActivitiesSub').disabled = !actEnabledAndChecked;
                if (!actEnabledAndChecked) {
                    document.getElementById('dupWithSubActivitiesSub').checked = false;
                }
            };

            msCheck.onchange();
        } else if (type === 'milestone') {
            optionsMs.classList.remove('d-none');
            optionsMs.querySelectorAll('input').forEach(i => i.disabled = false);

            const amsCheck = document.getElementById('dupWithActivitiesMs');
            const subMsContainer = document.getElementById('dupWithSubActivitiesContainerMs');
            
            amsCheck.onchange = () => {
                subMsContainer.style.opacity = amsCheck.checked ? '1' : '0.5';
                document.getElementById('dupWithSubActivitiesMs').disabled = !amsCheck.checked;
                if (!amsCheck.checked) {
                    document.getElementById('dupWithSubActivitiesMs').checked = false;
                }
            };
            amsCheck.onchange();

        } else if (type === 'activity') {
            optionsAct.classList.remove('d-none');
            optionsAct.querySelectorAll('input').forEach(i => i.disabled = false);
        } else {
            optionsSubAct.classList.remove('d-none');
        }

        new bootstrap.Modal(document.getElementById('modalDuplicate')).show();
    }

    async function submitDuplicateForm(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Duplicating...';

        try {
            const response = await fetch('{{ route("hierarchy.duplicate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalDuplicate')).hide();
                await refreshPageContent();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false, heightAuto: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Oops...', text: data.message || 'Gagal menduplikasi item.', heightAuto: false });
            }
        } catch (error) {
            console.error('Duplicate Error:', error);
            Swal.fire({ icon: 'error', title: 'Galat', text: 'Terjadi kesalahan sistem.', heightAuto: false });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Duplikasi Sekarang';
        }
    }

    function recalculateHierarchyNumbering() {
        const programPrefix = '{{ $program->prefix ? $program->prefix . "." : "" }}';
        
        // Recalculate Sub Programs
        const subCards = document.querySelectorAll('.sub-card');
        subCards.forEach((subCard, subIdx) => {
            const subNum = programPrefix + (subIdx + 1);
            const subNumEl = subCard.querySelector('.sub-num');
            if (subNumEl) subNumEl.textContent = subNum;

            // Recalculate Milestones within this sub program
            const msCards = subCard.querySelectorAll('.milestone-card');
            
            let mGroup = 1;
            let mCounter = 0;
            
            let krGroup = 1;
            let krCounter = 0;
            
            msCards.forEach((msCard) => {
                const msNumEl = msCard.querySelector('.ms-num');
                const type = msNumEl ? msNumEl.dataset.type : 'milestone';
                
                let msNum = '';
                let actPrefix = '';
                
                if (type === 'divider') {
                    mGroup++;
                    mCounter = 0;
                    if (msNumEl) msNumEl.textContent = '';
                } else if (type === 'key_result') {
                    krCounter++;
                    msNum = 'KR.' + krCounter;
                    actPrefix = krCounter;
                    if (msNumEl) msNumEl.textContent = msNum;
                } else {
                    mCounter++;
                    msNum = 'M.' + mGroup + '.' + mCounter;
                    actPrefix = mGroup + '.' + mCounter;
                    if (msNumEl) msNumEl.textContent = msNum;
                }

                // Recalculate Activities within this milestone
                const actItems = msCard.querySelectorAll('.activity-item');
                actItems.forEach((actItem, actIdx) => {
                    const actNumEl = actItem.querySelector('.act-num');
                    const actLevel = actPrefix + '.' + (actIdx + 1);
                    if (actNumEl) {
                        actNumEl.textContent = actLevel;
                    }

                    // Recalculate Sub-Activities within this Activity
                    const actId = actItem.dataset.id;
                    const subActContainer = document.querySelector(`.sub-activities-container[data-act-id="${actId}"]`);
                    if (subActContainer) {
                        const subActItems = subActContainer.querySelectorAll('.sub-activity-item');
                        subActItems.forEach((subItem, subIdx) => {
                            const subNumEl = subItem.querySelector('.sub-act-num');
                            if (subNumEl) {
                                subNumEl.textContent = actLevel + '.' + (subIdx + 1);
                            }
                        });
                    }
                });
            });
        });
    }

    function updateHierarchyOrder(type, order, parentId = null) {
        fetch('{{ route("hierarchy.update-order") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type, order: order, parent_id: parentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(type + ' order updated successfully');
            }
        })
        .catch(error => console.error('Error updating order:', error));
    }

// ===== AJAX TAB LOADER =====
document.addEventListener('DOMContentLoaded', function() {
    const ajaxTabs = document.querySelectorAll('.ajax-tab');
    
    ajaxTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            const targetId = this.getAttribute('data-bs-target');
            const url = this.getAttribute('data-url');
            const targetPane = document.querySelector(targetId);
            
            // Only load if it hasn't been loaded yet
            if (!this.hasAttribute('data-loaded')) {
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        targetPane.innerHTML = html;
                        this.setAttribute('data-loaded', 'true');
                        
                        // Execute inline scripts returned by AJAX SEQUENTIALLY
                        const scripts = Array.from(targetPane.querySelectorAll('script'));
                        
                        const loadScript = (oldScript) => {
                            return new Promise((resolve, reject) => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                
                                if (oldScript.src) {
                                    // External script, wait for onload
                                    newScript.onload = resolve;
                                    newScript.onerror = reject;
                                    oldScript.parentNode.replaceChild(newScript, oldScript);
                                } else {
                                    // Inline script, execute immediately
                                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                    oldScript.parentNode.replaceChild(newScript, oldScript);
                                    resolve();
                                }
                            });
                        };

                        // Helper to run scripts natively in order
                        const runScriptsSequentially = async () => {
                            for (let script of scripts) {
                                await loadScript(script);
                            }
                        };
                        runScriptsSequentially().catch(err => console.error('Error executing injected script:', err));
                    })
                    .catch(err => {
                        console.error('Error loading tab content:', err);
                        targetPane.innerHTML = `<div class="alert alert-danger m-4">Gagal memuat konten. Silakan muat ulang halaman.</div>`;
                    });
            } else {
                // If already loaded, trigger resize event which might help some libraries
                if (this.id === 'timeline-tab') {
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 50);
                }
            }

            // Additional check for timeline tab after first injection
            if (this.id === 'timeline-tab') {
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 300);
            }
        });
    });
});

// ===== ATTACHMENT MANAGEMENT =====
let _attachCurrentType = '';
let _attachCurrentId   = 0;
let _attachCurrentName = '';

// All attachments data passed from Blade (JSON)
let _allAttachments = [];
</script>
<script id="attachments-data-script" type="application/json">@json($attachmentsData)</script>
<script>
_allAttachments = JSON.parse(document.getElementById('attachments-data-script').textContent);

function openAttachmentModal(type, id, name) {
    _attachCurrentType = type;
    _attachCurrentId   = id;
    _attachCurrentName = name;

    document.getElementById('attachmentModalTitle').textContent = name;

    // Find attachments for this entity
    const entity = _allAttachments.find(e => e.attachable_type === type && e.attachable_id === id);
    const attachments = entity ? entity.attachments : [];

    const typeColors = {
        'RAB':      { bg: '#dbeafe', color: '#1d4ed8' },
        'Evidence': { bg: '#d1fae5', color: '#065f46' },
        'Paparan':  { bg: '#fef3c7', color: '#92400e' },
        'Other':    { bg: '#f1f5f9', color: '#475569' },
    };

    const iconsMap = {
        'pdf':          'fa-file-pdf text-danger',
        'word':         'fa-file-word text-primary',
        'document':     'fa-file-word text-primary',
        'excel':        'fa-file-excel text-success',
        'spreadsheet':  'fa-file-excel text-success',
        'presentation': 'fa-file-powerpoint text-warning',
        'powerpoint':   'fa-file-powerpoint text-warning',
        'image':        'fa-file-image text-info',
        'zip':          'fa-file-zipper text-secondary',
    };

    function getIcon(mime) {
        if (!mime) return 'fa-file text-muted';
        for (const [k, v] of Object.entries(iconsMap)) {
            if (mime.includes(k)) return v;
        }
        return 'fa-file text-muted';
    }

    function humanSize(bytes) {
        if (bytes >= 1048576) return (bytes/1048576).toFixed(1) + ' MB';
        if (bytes >= 1024)    return (bytes/1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    const body = document.getElementById('attachmentListBody');
    if (attachments.length === 0) {
        body.innerHTML = '<p class="text-muted text-center fst-italic py-4" style="font-size:0.82rem;">Belum ada lampiran. Klik <strong>Upload</strong> untuk menambahkan.</p>';
    } else {
        // Group by type
        const groups = {};
        attachments.forEach(a => {
            if (!groups[a.type]) groups[a.type] = [];
            groups[a.type].push(a);
        });

        let html = '';
        for (const [t, items] of Object.entries(groups)) {
            const tc = typeColors[t] || typeColors['Other'];
            html += `<div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background:${tc.bg}; color:${tc.color}; font-size:0.68rem; border:1px solid ${tc.color}33;">${t}</span>
                    <span class="text-muted" style="font-size:0.68rem;">${items.length} file</span>
                </div>
                <div class="d-flex flex-column gap-2">`;
            items.forEach(a => {
                const icon = getIcon(a.mime_type);
                html += `<div class="d-flex align-items-center gap-3 px-3 py-2 rounded-3 border" style="background:#fafafa;">
                    <i class="fa-solid ${icon} fs-5 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold text-dark text-truncate" style="font-size:0.82rem;">${a.name}</div>
                        <div class="text-muted" style="font-size:0.68rem;">${a.original_filename} · ${humanSize(a.file_size)}</div>
                        ${a.description ? `<div class="text-muted fst-italic" style="font-size:0.68rem;">${a.description}</div>` : ''}
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="/attachments/${a.id}/download" class="btn btn-sm px-2" style="background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; font-size:0.7rem;" title="Download">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm px-2" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:0.7rem;" title="Hapus"
                                onclick="deleteHierarchyItem('/attachments/${a.id}', 'Hapus lampiran ini?', async () => { await refreshPageContent(); openAttachmentModal(_attachCurrentType, _attachCurrentId, _attachCurrentName); })">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            html += `</div></div>`;
        }
        body.innerHTML = html;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalAttachments'));
    modal.show();
}

function openUploadAttachmentModal() {
    // Close list modal first, then open upload modal
    bootstrap.Modal.getInstance(document.getElementById('modalAttachments')).hide();
    document.getElementById('uploadAttachableType').value = _attachCurrentType;
    document.getElementById('uploadAttachableId').value   = _attachCurrentId;
    document.getElementById('uploadAttachmentLabel').textContent = _attachCurrentName;
    // Reset form
    document.getElementById('uploadAttachmentForm').reset();
    // De-select type radios
    document.querySelectorAll('.att-type-radio').forEach(r => r.checked = false);
    document.querySelectorAll('.att-type-label').forEach(l => l.style.fontWeight = 'normal');
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('modalUploadAttachment')).show();
    }, 300);
}

// Radio type visual selector
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.att-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.att-type-label').forEach(l => l.style.outline = 'none');
            if (this.checked) {
                this.closest('label').style.outline = '2px solid currentColor';
            }
        });
    });
});

// ===== HIERARCHY ACCORDION & SEARCH =====
function expandAll() {
    document.querySelectorAll('.hierarchy-collapse').forEach(function(el) {
        let bsCollapse = bootstrap.Collapse.getInstance(el) || new bootstrap.Collapse(el, {toggle: false});
        bsCollapse.show();
    });
}

function collapseAll() {
    document.querySelectorAll('.hierarchy-collapse').forEach(function(el) {
        let bsCollapse = bootstrap.Collapse.getInstance(el) || new bootstrap.Collapse(el, {toggle: false});
        bsCollapse.hide();
    });
}  

document.getElementById('hierarchySearch')?.addEventListener('input', function(e) {
    let term = e.target.value.toLowerCase();
    
    document.querySelectorAll('.sub-card').forEach(sub => {
        let subMatches = sub.getAttribute('data-name').includes(term);
        let hasVisibleMs = false;
        
        sub.querySelectorAll('.milestone-card').forEach(ms => {
            let msMatches = ms.getAttribute('data-name').includes(term);
            let hasVisibleAct = false;
            
            ms.querySelectorAll('.activity-item').forEach(act => {
                let actMatches = act.getAttribute('data-name')?.includes(term);
                if (actMatches || msMatches || subMatches || term === '') {
                    act.style.display = '';
                    hasVisibleAct = true;
                } else {
                    act.style.display = 'none';
                }
            });
            
            if (msMatches || subMatches || hasVisibleAct || term === '') {
                ms.style.display = '';
                hasVisibleMs = true;
                // auto-expand if searching
                if(term !== '' && hasVisibleAct) {
                   let c = ms.querySelector('.ms-collapse');
                   if(c) { let b = bootstrap.Collapse.getInstance(c) || new bootstrap.Collapse(c, {toggle: false}); b.show(); }
                }
            } else {
                ms.style.display = 'none';
            }
        });
        
        if (subMatches || hasVisibleMs || term === '') {
            sub.style.display = '';
            // auto expand
            if(term !== '' && hasVisibleMs) {
                let c = sub.querySelector('.sub-collapse');
                if(c) { let b = bootstrap.Collapse.getInstance(c) || new bootstrap.Collapse(c, {toggle: false}); b.show(); }
            }
        } else {
            sub.style.display = 'none';
        }
    });

    // Handle "No result found" message
    let anyVisible = false;
    document.querySelectorAll('.sub-card').forEach(sub => {
        if (sub.style.display !== 'none') anyVisible = true;
    });

    let noResultsEl = document.getElementById('noHierarchyResults');
    if (noResultsEl) {
        if (!anyVisible && term !== '') {
            noResultsEl.classList.remove('d-none');
        } else {
            noResultsEl.classList.add('d-none');
        }
    }
});
</script>
@endpush
@endsection
