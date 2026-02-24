<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css" />
<style>
    /* ===== WRAPPER & CONTAINER ===== */
    .gantt-wrapper {
        background: #f8fafc;
        padding: 20px 20px 24px 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow-x: auto;
    }
    .gantt-container {
        overflow: visible;
        background: transparent;
    }
    /* ===== GRID IMPROVEMENTS ===== */
    .gantt .grid-header {
        fill: #f1f5f9;
        stroke: #e2e8f0;
    }
    .gantt .grid-row {
        fill: #ffffff;
    }
    .gantt .grid-row:nth-child(even) {
        fill: #f8fafc;
    }
    .gantt .row-line {
        stroke: #e2e8f0;
    }
    .gantt .tick {
        stroke: #cbd5e1;
        stroke-width: 0.3;
    }
    .gantt .today-highlight {
        fill: #fef9c3;
        opacity: 0.5;
    }

    /* ===== DATE HEADER TEXT ===== */
    .gantt .lower-text, .gantt .upper-text {
        fill: #64748b;
        font-size: 12px;
        font-weight: 500;
    }

    /* ===== HIERARCHY BAR COLORS ===== */
    /* Program — Deep Indigo */
    .bar-program .bar         { fill: #4338ca; rx: 5; }
    .bar-program .bar-progress { fill: #312e81; }
    .bar-program .bar-label  { fill: #ffffff !important; font-size: 13px !important; font-weight: 600 !important; }

    /* Sub Program — Royal Blue */
    .bar-subprogram .bar         { fill: #2563eb; }
    .bar-subprogram .bar-progress { fill: #1d4ed8; }
    .bar-subprogram .bar-label  { fill: #ffffff !important; font-size: 12px !important; font-weight: 500 !important; }

    /* Milestone — Deep Purple */
    .bar-milestone .bar         { fill: #7c3aed; }
    .bar-milestone .bar-progress { fill: #6d28d9; }
    .bar-milestone .bar-label  { fill: #ffffff !important; font-size: 12px !important; font-weight: 500 !important; }

    /* Activity — Solid Emerald */
    .bar-activity .bar         { fill: #059669; }
    .bar-activity .bar-progress { fill: #047857; }
    .bar-activity .bar-label  { fill: #ffffff !important; font-size: 12px !important; font-weight: 500 !important; }

    /* ===== ACTIVITY STATUS OVERRIDES ===== */
    .status-Done .bar-progress, .status-done .bar-progress           { fill: #059669; }
    .status-On-Progress .bar-progress, .status-on-progress .bar-progress { fill: #d97706; }
    .status-On-Hold .bar-progress, .status-on-hold .bar-progress,
    .status-Draft .bar-progress, .status-draft .bar-progress         { fill: #475569; }
    .status-Cancelled .bar-progress, .status-cancelled .bar-progress,
    .status-Delayed .bar-progress, .status-delayed .bar-progress     { fill: #be123c; }
    .status-To-Do .bar-progress, .status-to-do .bar-progress         { fill: #0284c7; }

    /* ===== LABELS OUTSIDE BAR — stay readable on light background ===== */
    .bar-label.big {
        fill: #334155 !important;
        font-weight: 500 !important;
        font-size: 12px !important;
    }

    /* ===== DEPENDENCY ARROWS ===== */
    .gantt .arrow {
        stroke: #94a3b8;
        stroke-width: 1.5;
    }

    /* ===== POPUP ===== */
    .gantt-container .popup-wrapper .pointer {
        border-top-color: #1e293b;
    }
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

    <!-- Gantt Wrapper -->
    <div class="gantt-wrapper shadow-sm">
        <div class="gantt-container" id="gantt-target"></div>
    </div>
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

                // Helper: add days to a YYYY-MM-DD string
                const addDays = (dateStr, n) => {
                    const d = new Date(dateStr);
                    d.setDate(d.getDate() + n);
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                };

                // Store the real end dates so on_date_change can still report the correct value
                const realEndDates = {};

                let processedTasks = tasks.map(t => {
                    const startVal = t.start || minDateString;
                    let endVal   = t.end   || minDateString;

                    // Frappe Gantt v0.6.x: if start == end, resize handle overlaps drag handle
                    // and any drag will MOVE the bar instead of resizing it.
                    // Fix: pad end by 1 day for rendering only; real value is preserved in realEndDates.
                    realEndDates[t.id] = endVal;
                    if (endVal <= startVal) {
                        endVal = addDays(startVal, 1);
                    }

                    return {
                        id: t.id,
                        name: t.name,
                        start: startVal,
                        end: endVal,
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
                        // Normalize dates to start of day local time to prevent shifting
                        const normalizeDate = (d) => {
                            const date = new Date(d);
                            date.setHours(0, 0, 0, 0);
                            return date;
                        };

                        const formatDate = (date) => {
                            const d = normalizeDate(date);
                            const year = d.getFullYear();
                            const month = String(d.getMonth() + 1).padStart(2, '0');
                            const day = String(d.getDate()).padStart(2, '0');
                            return `${year}-${month}-${day}`;
                        };

                        const startStr = formatDate(start);
                        const endStr   = formatDate(end);

                        // Update realEndDates so popup stays accurate after interaction
                        realEndDates[task.id] = endStr;

                        fetch('/api/projects/gantt/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: task.id,
                                start: startStr,
                                end: endStr
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
                    let ganttContainer = document.querySelector('.gantt-wrapper');
                    
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
