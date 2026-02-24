<!-- CSS for Frappe Gantt -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css" />
<style>
    .gantt-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 0;
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

<div class="d-flex flex-column gap-3">
    <!-- View Mode Toggles -->
    <div class="d-flex justify-content-between align-items-center mb-0">
        <h5 class="fw-bold fs-6 mb-0 text-dark"><i class="fa-solid fa-chart-gantt text-primary me-2"></i>Project Timeline</h5>
        <div class="d-flex gap-2">
            <button onclick="changeViewMode('Quarter Day', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Quarter Day</button>
            <button onclick="changeViewMode('Half Day', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Half Day</button>
            <button onclick="changeViewMode('Day', this)" class="btn btn-primary btn-sm rounded shadow-sm view-btn active-btn">Day</button>
            <button onclick="changeViewMode('Week', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Week</button>
            <button onclick="changeViewMode('Month', this)" class="btn btn-outline-secondary btn-sm rounded view-btn">Month</button>
        </div>
    </div>

    <!-- Gantt Container -->
    <div class="gantt-container shadow-sm border border-light-subtle" id="gantt-target"></div>
</div>

<!-- JS for Frappe Gantt -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>
<script>
    (function() {
        let gantt;
        let programId = "{{ $program->id }}";
        let fetchUrl = '/api/projects/gantt?program_id=' + programId;

        fetch(fetchUrl)
            .then(response => response.json())
            .then(tasks => {
                if(tasks.length === 0) {
                    document.getElementById('gantt-target').innerHTML = "<p class='text-center text-muted py-5'>No hierarchical tasks available to schedule.</p>";
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
                    },
                    on_date_change: function(task, start, end) {
                        fetch('/api/projects/gantt/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: task.id,
                                start: start.toISOString().split('T')[0],
                                end: end.toISOString().split('T')[0]
                            })
                        }).catch(error => console.error('Error updating task:', error));
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
                        // The default FrappeGantt grid line has an x coordinate
                        // Alternatively, we can use the library's internal `scroll_to` method or set scrollLeft manually
                         // We can find the target x by looking for the .date_... or .tick line, but simpler is:
                        // Each day is (column_width). In "Day" view, column_width is typically 38px
                        // But since we let Frappe set the grid, let's use the date text placement:
                        let xAttr = dateLabel.getAttribute('x');
                        if(xAttr) {
                            ganttContainer.scrollLeft = parseFloat(xAttr) - 20; // 20px left padding buffer
                        }
                    } else {
                        // Fallback generic scroll - shift the view to start near minDate
                        gantt.set_scroll_position(targetDate);
                    }
                }, 100);
            })
            .catch(error => console.error('Error fetching Gantt Data:', error));

        // attach to global scope for the buttons
        window.changeViewMode = function(mode, btn) {
            if(gantt) {
                gantt.change_view_mode(mode);
                document.querySelectorAll('.view-btn').forEach(b => {
                    b.classList.remove('btn-primary', 'shadow-sm', 'active-btn');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary', 'shadow-sm', 'active-btn');
            }
        };
    })();
</script>
