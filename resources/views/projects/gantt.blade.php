@extends('layouts.app')

@section('title', 'Gantt Chart - ProMan')
@section('header_title', 'Project Gantt Chart')

@section('header_actions')
    <a href="{{ route('projects.export', ['program_id' => $selectedProgramId]) }}" class="btn btn-success btn-sm shadow-sm fw-medium px-3">
        <i class="fa-solid fa-file-excel me-2"></i> Export Weekly
    </a>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css" />
    <style>
        .gantt-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            overflow-x: auto;
        }
        
        .bar-program .bar-progress { fill: #4f46e5; }
        .bar-subprogram .bar-progress { fill: #0ea5e9; }
        .bar-milestone .bar-progress { fill: #8b5cf6; }
        
        .status-done .bar-progress { fill: #198754; }
        .status-delayed .bar-progress { fill: #dc3545; }
        .status-on-progress .bar-progress { fill: #fd7e14; }
        .status-not-started .bar-progress { fill: #6c757d; }
    </style>
@endpush

@section('content')
<div class="container-fluid d-flex flex-column gap-3">
    <div class="d-flex justify-content-between align-items-center mb-0">
        <p class="text-muted mb-0">Visualize the hierarchical timeline: Program > Sub Program > Milestone > Activity.</p>
        
        <form action="{{ route('projects.gantt') }}" method="GET" class="d-flex align-items-center gap-2">
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

    <!-- View Mode Toggles -->
    <div class="d-flex gap-2">
        <button onclick="changeViewMode('Quarter Day', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Quarter Day</button>
        <button onclick="changeViewMode('Half Day', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Half Day</button>
        <button onclick="changeViewMode('Day', this)" class="btn btn-primary btn-sm rounded shadow-sm view-btn active-btn">Day</button>
        <button onclick="changeViewMode('Week', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Week</button>
        <button onclick="changeViewMode('Month', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Month</button>
    </div>

    <!-- Gantt Container -->
    <div class="gantt-container shadow-sm" id="gantt-target"></div>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>
    <script>
        let gantt;

        document.addEventListener('DOMContentLoaded', function() {
            let programId = "{{ $selectedProgramId }}";
            let fetchUrl = '/api/projects/gantt' + (programId ? '?program_id=' + programId : '');

            fetch(fetchUrl)
                .then(response => response.json())
                .then(tasks => {
                    if(tasks.length === 0) {
                        document.getElementById('gantt-target').innerHTML = "<p class='text-center text-muted py-5'>No tasks available or you need to seed the database.</p>";
                        return;
                    }

                    // Find the earliest valid start date to use as a fallback for parent items without dates
                    let validStarts = tasks.map(t => t.start ? new Date(t.start).getTime() : null).filter(d => d !== null && !isNaN(d));
                    let minDateString = new Date().toISOString().split('T')[0];
                    if (validStarts.length > 0) {
                        let minDate = new Date(Math.min(...validStarts));
                        minDateString = minDate.toISOString().split('T')[0];
                    }

                    let processedTasks = tasks.map(t => {
                        return {
                            id: t.id,
                            name: t.name,
                            start: t.start || minDateString,
                            end: t.end || minDateString,
                            progress: t.progress,
                            dependencies: t.dependencies || '',
                            custom_class: t.custom_class || ''
                        };
                    });

                    let ganttOptions = {
                        view_mode: 'Day',
                        date_format: 'YYYY-MM-DD',
                        // Default padding is kept so the grid renders securely
                        custom_popup_html: function(task) {
                            return `
                                <div class="p-3 bg-white rounded shadow border text-sm" style="min-width: 200px; font-size: 0.85rem;">
                                    <h5 class="fw-bold border-bottom pb-2 mb-2 text-primary fs-6">${task.name}</h5>
                                    <div class="row g-2 text-muted mb-2">
                                        <div class="col-6"><span class="fw-semibold text-dark">Start:</span> ${task.start.split(' ')[0]}</div>
                                        <div class="col-6"><span class="fw-semibold text-dark">End:</span> ${task.end.split(' ')[0]}</div>
                                    </div>
                                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-dark">Progress:</span>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">${task.progress}%</span>
                                    </div>
                                </div>
                            `;
                        }
                    };

                    gantt = new Gantt('#gantt-target', processedTasks, ganttOptions);

                    // Auto-scroll to H-3 days from the first task
                    setTimeout(() => {
                        let targetDate = new Date(minDateString);
                        targetDate.setDate(targetDate.getDate() - 3);
                        let targetDateString = targetDate.toISOString().split('T')[0];
                        let dateLabel = document.querySelector(`.date-label[data-id="${targetDateString}"]`);
                        let ganttContainer = document.querySelector('.gantt-container');
                        
                        if (dateLabel && ganttContainer) {
                            let xAttr = dateLabel.getAttribute('x');
                            if(xAttr) {
                                ganttContainer.scrollLeft = parseFloat(xAttr) - 20; // 20px left padding buffer
                            }
                        } else {
                            gantt.set_scroll_position(targetDate);
                        }
                    }, 100);
                })
                .catch(error => console.error('Error fetching Gantt Data:', error));
        });

        function changeViewMode(mode, btn) {
            if(gantt) {
                gantt.change_view_mode(mode);
                
                document.querySelectorAll('.view-btn').forEach(b => {
                    b.classList.remove('btn-primary', 'shadow-sm', 'active-btn');
                    b.classList.add('btn-outline-secondary');
                });
                
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary', 'shadow-sm', 'active-btn');
            }
        }
    </script>
@endpush
