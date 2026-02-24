@extends('layouts.app')

@section('title', 'Project Calendar - ProMan')
@section('header_title', 'Project Calendar')

@section('header_actions')
    <a href="{{ route('projects.export', ['program_id' => $selectedProgramId]) }}" class="btn btn-success btn-sm shadow-sm fw-medium px-3">
        <i class="fa-solid fa-file-excel me-2"></i> Export Weekly
    </a>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
    <style>
        #calendar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
    </style>
@endpush

@section('content')
<div class="container-xl d-flex flex-column gap-3" style="max-width: 1200px;">
    <div class="d-flex justify-content-between align-items-center mb-0">
        <p class="text-muted mb-0">View all project activities on the calendar.</p>
        
        <form action="{{ route('projects.calendar') }}" method="GET" class="d-flex align-items-center gap-2">
            <label for="program_id" class="text-muted fw-medium text-nowrap mb-0 small">Filter Program:</label>
            <select name="program_id" id="program_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()" style="min-width: 200px;">
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ $selectedProgramId == $program->id ? 'selected' : '' }}>
                        {{ $program->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    
    <!-- Calendar Container -->
    <div id="calendar" class="shadow-sm"></div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listWeek'
                },
                events: '/api/projects/calendar' + ("{{ $selectedProgramId }}" ? "?program_id={{ $selectedProgramId }}" : ""),
                eventDidMount: function(info) {
                    info.el.title = "Status: " + info.event.extendedProps.status.toUpperCase() + " | Progress: " + info.event.extendedProps.progress + "%";
                }
            });

            calendar.render();
        });
    </script>
@endpush
