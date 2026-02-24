@extends('layouts.app')

@section('title', 'Program Detail - ProMan')
@section('header_title', 'Program Detail: ' . $program->name)

@section('header_actions')
    <div class="d-flex gap-2">
        <!-- <a href="{{ route('projects.gantt', ['program_id' => $program->id]) }}" class="btn btn-outline-primary btn-sm shadow-sm fw-medium px-3 d-flex align-items-center">
            <i class="fa-solid fa-chart-gantt me-2"></i> Timeline Chart
        </a>
        <a href="{{ route('projects.calendar', ['program_id' => $program->id]) }}" class="btn btn-outline-primary btn-sm shadow-sm fw-medium px-3 d-flex align-items-center">
            <i class="fa-regular fa-calendar-days me-2"></i> Calendar
        </a> -->
        <a href="{{ route('programs.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-medium px-3 d-flex align-items-center">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
        </a>
    </div>
@endsection

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
        $allHave = $acts->every(fn($a) => $a->bobot !== null);
        if ($allHave) {
            return min(100, $acts->sum(fn($a) => $a->progress * $a->bobot / 100));
        }
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
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-4">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(255,255,255,0.15);">
                                <i class="fa-solid fa-folder-open fs-4"></i>
                            </div>
                            <div>
                                <p class="text-white opacity-60 mb-0" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em;">Program</p>
                                <h2 class="fs-4 fw-bold text-white mb-0">{{ $program->name }}</h2>
                            </div>
                        </div>
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
                    <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
                        {{-- Edit Program --}}
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <button type="button" class="btn btn-sm fw-semibold d-flex align-items-center gap-2 px-3"
                                style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: white;"
                                onclick="openEditProgram({{ $program->id }}, '{{ addslashes($program->name) }}', '{{ addslashes($program->description ?? '') }}', '{{ $program->start_date ? $program->start_date->format('Y-m-d') : '' }}', '{{ $program->end_date ? $program->end_date->format('Y-m-d') : '' }}')"
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
                        <form action="{{ route('programs.destroy', $program->id) }}" method="POST" onsubmit="return confirm('Hapus program ini secara permanen?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm fw-semibold d-flex align-items-center gap-2 px-3"
                                    style="background: rgba(239,68,68,0.3); border: 1px solid rgba(239,68,68,0.5); color: #fca5a5;">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    @php
                        $heroStats = [
                            ['label' => 'Sub Programs', 'value' => $program->subPrograms->count(), 'icon' => 'fa-diagram-project'],
                            ['label' => 'Activities',   'value' => $totalActs,                     'icon' => 'fa-list-check'],
                            ['label' => 'Completed',    'value' => $doneActs,                      'icon' => 'fa-circle-check'],
                            ['label' => 'Avg Progress', 'value' => $avgProgress . '%',             'icon' => 'fa-gauge-high'],
                        ];
                    @endphp
                    @foreach($heroStats as $stat)
                    <div class="col-6 col-md-3">
                        <div class="rounded-3 p-3" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid {{ $stat['icon'] }} opacity-60" style="font-size: 0.8rem;"></i>
                                <span class="text-white opacity-60" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.07em;">{{ $stat['label'] }}</span>
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
        // Group all activities by system status with hierarchy info
        $activitiesByStatus = ['Upcoming' => [], 'Active' => [], 'Delayed' => [], 'Completed' => []];
        foreach($program->subPrograms as $sub) {
            foreach($sub->milestones as $ms) {
                foreach($ms->activities as $act) {
                    $sys = $act->system_status;
                    if(isset($activitiesByStatus[$sys])) {
                        $activitiesByStatus[$sys][] = [
                            'sub'  => $sub->name,
                            'ms'   => $ms->name,
                            'act'  => $act->name,
                            'pct'  => $act->progress,
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
            $statusActs   = $activitiesByStatus[$label];
            $chipId       = 'chip-' . strtolower($label);
        @endphp
        <div id="{{ $chipId }}"
             class="d-inline-flex align-items-center gap-1 rounded-pill px-3 py-1 fw-semibold border chip-status"
             style="font-size: 0.72rem; color: {{ $c['color'] }}; background: {{ $c['bg'] }}; border-color: {{ $c['color'] }}33 !important; cursor: pointer;">
            <i class="fa-solid {{ $c['icon'] }}"></i>
            <span>{{ $label }}</span>
            <span class="fw-black ms-1 px-1 rounded-pill" style="background: {{ $c['color'] }}18;">{{ count($statusActs) }}</span>
        </div>
        {{-- hidden popover content for this chip --}}
        <div id="{{ $chipId }}-content" class="d-none">
            @if(count($statusActs) === 0)
                <div class="text-muted fst-italic" style="font-size:0.78rem;">Tidak ada aktivitas dengan status ini.</div>
            @else
                <ol class="mb-0 ps-3" style="font-size:0.76rem; max-height:220px; overflow-y:auto;">
                    @foreach($statusActs as $item)
                        <li class="mb-1">
                            <span class="text-muted" style="font-size:0.68rem;">{{ $item['sub'] }} › {{ $item['ms'] }}</span><br>
                            <strong>{{ $item['act'] }}</strong> <span class="text-muted">({{ $item['pct'] }}%)</span>
                        </li>
                    @endforeach
                </ol>
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
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseSub{{ $sub->id }}" aria-expanded="true">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 36px; height: 36px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fa-solid fa-chevron-down text-white icon-collapse transition-transform" id="iconSub{{ $sub->id }}"></i>
                    </div>
                    <div>
                        <p class="text-white opacity-60 mb-0" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em;">Sub Program</p>
                        <h5 class="fw-bold text-white mb-0 fs-6"><span class="sub-num">{{ $loop->iteration }}</span>. {{ $sub->name }}</h5>
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
                    {{-- Add Milestone --}}
                    @if($userRole === 'administrator' || $userRole === 'manager')
                    <button type="button"
                            class="btn btn-sm fw-semibold d-flex align-items-center gap-1 px-2"
                            style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white; font-size: 0.72rem;"
                            data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                            onclick="setMilestoneSubProgram({{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                        <i class="fa-solid fa-plus"></i> Milestone
                    </button>
                    @endif
                    {{-- Delete Sub Program --}}
                    @if($userRole === 'administrator')
                    <form action="{{ route('sub_programs.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Hapus sub program ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm d-flex align-items-center gap-1 px-2"
                                style="background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5; font-size: 0.72rem;">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div id="collapseSub{{ $sub->id }}" class="collapse show hierarchy-collapse sub-collapse">
                <div style="height: 4px; background: #e2e8f0;">
                    <div style="width: {{ $subAvg }}%; height: 100%; background: linear-gradient(to right, #3b82f6, #6366f1);"></div>
                </div>
                <div class="card-body p-3 d-flex flex-column gap-3 bg-light sub-body milestones-container" data-sub-id="{{ $sub->id }}" id="milestones-{{ $sub->id }}">
                @forelse($sub->milestones as $ms)
                @php
                    $msActs  = $ms->activities;
                    $msTotal = $msActs->count();
                    $msAvg   = round($calcMsProgress($ms));
                @endphp
                <div class="card border-0 shadow-sm overflow-hidden milestone-card mb-3" data-name="{{ strtolower($ms->name) }}" data-id="{{ $ms->id }}">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                        @if($userRole === 'administrator' || $userRole === 'manager')
                        <div class="ms-drag-handle py-1 px-1 me-1" style="cursor: grab;">
                            <i class="fa-solid fa-grip-vertical text-muted opacity-50"></i>
                        </div>
                        @endif
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseMs{{ $ms->id }}" aria-expanded="true">
                            <div class="rounded-2 d-flex align-items-center justify-content-center transition-transform icon-collapse"
                                 style="width: 28px; height: 28px; background: #dbeafe; border: 1px solid #bfdbfe;" id="iconMs{{ $ms->id }}">
                                <i class="fa-solid fa-chevron-down" style="color: #3b82f6; font-size: 0.7rem;"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0" style="font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.07em;">Milestone</p>
                                <h6 class="fw-semibold text-dark mb-0" style="font-size: 0.85rem;"><span class="ms-num">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</span>. {{ $ms->name }}
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
                            <form action="{{ route('milestones.destroy', $ms->id) }}" method="POST" onsubmit="return confirm('Hapus milestone ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm p-1" style="color: #ef4444; background: none; border: none; font-size: 0.75rem;">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    <div id="collapseMs{{ $ms->id }}" class="collapse show hierarchy-collapse ms-collapse">
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
                                    <th class="px-3 py-2 fw-semibold border-bottom text-muted text-center" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; width: 70px;">Bobot</th>
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
                                            <div>
                                                <div class="fw-semibold text-dark"><span class="act-num">{{ $loop->parent->parent->iteration }}.{{ $loop->parent->iteration }}.{{ $loop->iteration }}</span>. {{ $act->name }}</div>
                                                @if($act->description)
                                                    <div class="text-muted" style="font-size: 0.68rem;">{{ Str::limit($act->description, 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center" style="font-size: 0.68rem; color: #64748b;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>{{ $act->start_date ? $act->start_date->format('d M y') : '-' }}</span>
                                            <i class="fa-solid fa-arrow-down opacity-30" style="font-size: 0.55rem;"></i>
                                            <span>{{ $act->end_date ? $act->end_date->format('d M y') : '-' }}</span>
                                        </div>
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
                                    <td class="px-3 py-2 text-center">
                                        @if($act->bobot !== null)
                                        <span class="badge rounded-pill fw-bold" style="background: #ede9fe; color: #6d28d9; font-size: 0.65rem; border: 1px solid #ddd6fe;">
                                            {{ $act->bobot }}%
                                        </span>
                                        @else
                                        <span class="text-muted opacity-30" style="font-size: 0.75rem;">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            {{-- Edit Activity --}}
                                            @if($userRole === 'administrator' || $userRole === 'manager')
                                            <button type="button" class="btn btn-sm p-1 px-2"
                                                    style="color: #4338ca; background: #e0e7ff; border: 1px solid #c7d2fe; font-size: 0.7rem;"
                                                    title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditActivity"
                                                    onclick="openEditActivity({{ $act->id }}, {{ $act->milestone_id }}, '{{ addslashes($act->name) }}', '{{ $act->bobot ?? '' }}', '{{ addslashes($act->description ?? '') }}', '{{ $act->start_date ? $act->start_date->format('Y-m-d') : '' }}', '{{ $act->end_date ? $act->end_date->format('Y-m-d') : '' }}', {{ $act->progress }}, '{{ $act->status }}', '{{ addslashes($act->uic ?? '') }}', '{{ addslashes($act->pic ?? '') }}')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            @endif
                                            {{-- Delete Activity --}}
                                            @if($userRole === 'administrator')
                                            <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('Hapus activity ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm p-1 px-2 text-danger" style="background: #fef2f2; border: 1px solid #fecaca; font-size: 0.7rem;" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                            {{-- Attachments Activity --}}
                                            <button type="button"
                                                    class="position-relative btn btn-sm px-2 py-1 d-inline-flex flex-row align-items-center justify-content-center text-nowrap"
                                                    style="color: #92400e; background: #fef3c7; border: 1px solid #fde68a; font-size: 0.7rem; height: 26px;"
                                                    title="Lampiran"
                                                    onclick="openAttachmentModal('activity', {{ $act->id }}, '{{ addslashes($act->name) }}')">
                                                <i class="fa-solid fa-paperclip"></i>
                                                @if($act->attachments->count() > 0)
                                                    <span class="badge rounded-pill ms-1 position-absolute" style="background: #f59e0b; color: white; font-size: 0.55rem; padding: 0.15em 0.4em; top:-5px; right:-5px">{{ $act->attachments->count() }}</span>
                                                @endif
                                            </button>
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
                @empty
                    <div class="text-center py-4 rounded-3 border border-2 border-dashed bg-white" style="border-color: #cbd5e1 !important;">
                        <i class="fa-solid fa-flag text-muted opacity-30 fs-3 d-block mb-2"></i>
                        <p class="small text-muted mb-2">No milestones yet.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#modalAddMilestone"
                                onclick="setMilestoneSubProgram({{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                            <i class="fa-solid fa-plus me-1"></i> Add Milestone
                        </button>
                    </div>
                @endforelse
                </div> <!-- End sub-body -->
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
            <div class="bg-white rounded-5 shadow-sm p-4 p-md-5 border-0">
                <h5 class="fw-bold mb-4 text-dark fs-5"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Activity Log</h5>
                
                @if($activityLogs->isEmpty())
                    <div class="text-center py-5">
                        <i class="fa-solid fa-wind text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted">Belum ada riwayat aktivitas yang tercatat.</p>
                    </div>
                @else
                    <div class="position-relative ms-2 ms-md-4">
                        {{-- Vertical Line --}}
                        <div class="position-absolute top-0 bottom-0" style="left: 15px; width: 2px; background: #e2e8f0; transform: translateX(-50%);"></div>

                        <div class="d-flex flex-column gap-4">
                            @foreach($activityLogs as $log)
                                @php 
                                    $color = $log->action_color; 
                                    $changed = $log->changed_fields;
                                @endphp
                                <div class="position-relative ps-5">
                                    {{-- Timeline node --}}
                                    <div class="position-absolute rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                         style="left: 15px; top: 0; width: 32px; height: 32px; background: {{ $color['bg'] }}; color: {{ $color['color'] }}; border: 2px solid white; transform: translateX(-50%); z-index: 1;">
                                        <i class="fa-solid {{ $color['icon'] }}" style="font-size: 0.8rem;"></i>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="card border-0 shadow-sm rounded-4" style="background: #f8fafc; border: 1px solid #f1f5f9 !important;">
                                        <div class="card-body p-3 p-md-4">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                <div>
                                                    <span class="badge rounded-pill fw-semibold mb-2" style="background: {{ $color['bg'] }}; color: {{ $color['color'] }}; font-size: 0.65rem; border: 1px solid {{ $color['color'] }}33;">
                                                        {{ strtoupper($log->action) }}
                                                    </span>
                                                    <span class="ms-2 badge rounded-pill bg-secondary bg-opacity-10 text-secondary fw-semibold" style="font-size: 0.65rem;">
                                                        {{ strtoupper($log->entity_label) }}
                                                    </span>
                                                    <h6 class="fw-bold text-dark mb-0 mt-1" style="font-size: 0.9rem;">{{ ucfirst($log->description) }}</h6>
                                                </div>
                                                <div class="text-end">
                                                    <span class="text-muted fw-semibold d-block" style="font-size: 0.72rem;">{{ $log->created_at->format('d M Y, H:i') }}</span>
                                                    <span class="text-muted d-block opacity-75" style="font-size: 0.65rem;">{{ $log->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>

                                            @if($log->action === 'updated' && !empty($changed))
                                                <div class="mt-3 bg-white rounded-3 border p-3">
                                                    <p class="text-muted fw-semibold mb-2" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Perubahan Data</p>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 0.78rem;">
                                                            <tbody>
                                                                @foreach($changed as $c)
                                                                    <tr>
                                                                        <td class="fw-semibold text-secondary" style="width: 25%;">{{ ucfirst(str_replace('_', ' ', $c['field'])) }}</td>
                                                                        <td class="text-danger opacity-75 text-decoration-line-through text-truncate" style="max-width: 150px;" title="{{ $c['old'] }}">{{ $c['old'] ?? '-' }}</td>
                                                                        <td style="width: 20px;" class="text-center text-muted"><i class="fa-solid fa-arrow-right"></i></td>
                                                                        <td class="text-success fw-medium text-truncate" style="max-width: 150px;" title="{{ $c['new'] }}">{{ $c['new'] ?? '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        {{-- TAB 6: MEMBERS --}}
        <div class="tab-pane fade" id="member" role="tabpanel" aria-labelledby="member-tab">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom border-light">
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
            <form action="{{ route('sub_programs.store') }}" method="POST">
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
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm">
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

{{-- ADD Milestone --}}
<div class="modal fade" id="modalAddMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('milestones.store') }}" method="POST">
                @csrf
                <input type="hidden" name="sub_program_id" id="milestoneSubProgramId" value="">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #dbeafe;">
                            <i class="fa-solid fa-flag-checkered" style="color: #3b82f6;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Tambah Milestone</h5>
                            <p class="text-muted mb-0 small" id="milestoneSubLabel">Sub Program: —</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Milestone <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Desain Database" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm">
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
            <form action="{{ route('activities.store') }}" method="POST">
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
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Desain ERD" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control form-control-sm" required>
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
            <form id="editProgramForm" action="" method="POST">
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Program <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editProgramName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" id="editProgramDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="editProgramStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="editProgramEnd" class="form-control form-control-sm">
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

{{-- EDIT Sub Program --}}
<div class="modal fade" id="modalEditSubProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editSubProgramForm" action="" method="POST">
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
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="editSubProgramStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="editSubProgramEnd" class="form-control form-control-sm">
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

{{-- EDIT Milestone --}}
<div class="modal fade" id="modalEditMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editMilestoneForm" action="" method="POST">
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
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Milestone <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editMilestoneName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" id="editMilestoneBobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deskripsi</label>
                        <textarea name="description" id="editMilestoneDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="editMilestoneStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="editMilestoneEnd" class="form-control form-control-sm">
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

{{-- EDIT Activity --}}
<div class="modal fade" id="modalEditActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editActivityForm" action="" method="POST">
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
                        <div class="col-9">
                            <label class="form-label fw-semibold small">Nama Activity <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editActivityName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-semibold small">Bobot <span class="text-muted fw-normal">(%)</span></label>
                            <input type="number" name="bobot" id="editActivityBobot" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi</label>
                            <textarea name="description" id="editActivityDesc" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="editActivityStart" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="editActivityEnd" class="form-control form-control-sm" required>
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

<style>
    .border-dashed { border-style: dashed !important; }
</style>

@push('scripts')
<script>
    // ---- ADD helpers ----
    function setMilestoneSubProgram(subId, subName) {
        document.getElementById('milestoneSubProgramId').value = subId;
        document.getElementById('milestoneSubLabel').textContent = 'Sub Program: ' + subName;
    }
    function setActivityMilestone(msId, msName) {
        document.getElementById('activityMilestoneId').value = msId;
        document.getElementById('activityMilestoneLabel').textContent = 'Milestone: ' + msName;
    }

    // ---- EDIT Program ----
    function openEditProgram(id, name, desc, start, end) {
        document.getElementById('editProgramForm').action = '/programs/' + id;
        document.getElementById('editProgramName').value = name;
        document.getElementById('editProgramDesc').value = desc;
        document.getElementById('editProgramStart').value = start;
        document.getElementById('editProgramEnd').value = end;
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
    }

    // ---- EDIT Milestone ----
    function openEditMilestone(id, subProgramId, name, bobot, desc, start, end) {
        document.getElementById('editMilestoneForm').action = '/milestones/' + id;
        document.getElementById('editMilestoneLabel').textContent = 'Editing: ' + name;
        document.getElementById('editMilestoneSubProgramId').value = subProgramId;
        document.getElementById('editMilestoneName').value = name;
        document.getElementById('editMilestoneBobot').value = bobot || '';
        document.getElementById('editMilestoneDesc').value = desc;
        document.getElementById('editMilestoneStart').value = start;
        document.getElementById('editMilestoneEnd').value = end;
    }

    // ---- EDIT Activity ----
    function openEditActivity(id, milestoneId, name, bobot, desc, start, end, progress, status, uic, pic) {
        document.getElementById('editActivityForm').action = '/activities/' + id;
        document.getElementById('editActivityLabel').textContent = 'Editing: ' + name;
        document.getElementById('editActivityMilestoneIdHidden').value = milestoneId;
        document.getElementById('editActivityName').value = name;
        document.getElementById('editActivityBobot').value = bobot || '';
        document.getElementById('editActivityDesc').value = desc;
        document.getElementById('editActivityStart').value = start;
        document.getElementById('editActivityEnd').value = end;
        document.getElementById('editActivityProgress').value = progress;
        document.getElementById('editActivityStatus').value = status;
        document.getElementById('editActivityUic').value = uic;
        document.getElementById('editActivityPic').value = pic;
    }

    // ---- Init popovers (status chips) using DOM content divs ----
    document.addEventListener('DOMContentLoaded', function () {
        ['upcoming','active','delayed','completed'].forEach(function(status) {
            var chip    = document.getElementById('chip-' + status);
            var content = document.getElementById('chip-' + status + '-content');
            if (!chip || !content) return;
            new bootstrap.Popover(chip, {
                html: true,
                trigger: 'hover focus',
                sanitize: false,
                title: chip.querySelector('i').outerHTML + ' ' + chip.querySelector('span').textContent.trim(),
                content: content.innerHTML,
                placement: 'bottom'
            });
        });
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
            <form id="uploadAttachmentForm" action="{{ route('attachments.store') }}" method="POST" enctype="multipart/form-data">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Sortable Sub Programs
    const subContainer = document.getElementById('sub-programs-container');
    if (subContainer) {
        new Sortable(subContainer, {
            animation: 150,
            handle: '.sub-drag-handle',
            ghostClass: 'bg-primary',
            ghostClass: 'opacity-25',
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
            handle: '.ms-drag-handle',
            ghostClass: 'bg-primary',
            ghostClass: 'opacity-25',
            onEnd: function (evt) {
                const order = Array.from(container.querySelectorAll('.milestone-card')).map(el => el.dataset.id);
                updateHierarchyOrder('milestone', order);
                recalculateHierarchyNumbering();
            }
        });
    });

    // 3. Sortable Activities
    document.querySelectorAll('.activities-container').forEach(container => {
        new Sortable(container, {
            animation: 150,
            handle: '.act-drag-handle',
            ghostClass: 'bg-primary',
            ghostClass: 'opacity-10',
            onEnd: function (evt) {
                const order = Array.from(container.querySelectorAll('.activity-item')).map(el => el.dataset.id);
                updateHierarchyOrder('activity', order);
                recalculateHierarchyNumbering();
            }
        });
    });

    function recalculateHierarchyNumbering() {
        // Recalculate Sub Programs
        const subCards = document.querySelectorAll('.sub-card');
        subCards.forEach((subCard, subIdx) => {
            const subNum = subIdx + 1;
            const subNumEl = subCard.querySelector('.sub-num');
            if (subNumEl) subNumEl.textContent = subNum;

            // Recalculate Milestones within this sub program
            const msCards = subCard.querySelectorAll('.milestone-card');
            msCards.forEach((msCard, msIdx) => {
                const msNum = subNum + '.' + (msIdx + 1);
                const msNumEl = msCard.querySelector('.ms-num');
                if (msNumEl) msNumEl.textContent = msNum;

                // Recalculate Activities within this milestone
                const actItems = msCard.querySelectorAll('.activity-item');
                actItems.forEach((actItem, actIdx) => {
                    const actNum = msNum + '.' + (actIdx + 1);
                    const actNumEl = actItem.querySelector('.act-num');
                    if (actNumEl) actNumEl.textContent = actNum;
                });
            });
        });
    }

    function updateHierarchyOrder(type, order) {
        fetch('{{ route("hierarchy.update-order") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ type: type, order: order })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(type + ' order updated successfully');
            }
        })
        .catch(error => console.error('Error updating order:', error));
    }
});

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
            }
        });
    });
});

// ===== ATTACHMENT MANAGEMENT =====
let _attachCurrentType = '';
let _attachCurrentId   = 0;
let _attachCurrentName = '';

// All attachments data passed from Blade (JSON)
const _allAttachments = @json($attachmentsData);

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
                        <form action="/attachments/${a.id}" method="POST" onsubmit="return confirm('Hapus lampiran ini?')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm px-2" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:0.7rem;" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
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
