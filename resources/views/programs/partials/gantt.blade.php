{{-- Frappe Gantt --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar { z-index: 9999 !important; }
    .gantt-wrapper {
        background: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: auto;
        min-height: 500px;
    }
    /* Hierarchy Colors */
    .gantt .bar-program .bar-progress { fill: #1e1b4b; } /* Deep Indigo */
    .gantt .bar-program .bar { fill: #312e81; opacity: 0.3; }
    
    .gantt .bar-subprogram .bar-progress { fill: #2563eb; } /* Blue */
    .gantt .bar-subprogram .bar { fill: #3b82f6; opacity: 0.3; }
    
    .gantt .bar-milestone .bar-progress { fill: #7c3aed; } /* Violet */
    .gantt .bar-milestone .bar { fill: #8b5cf6; opacity: 0.3; }
    
    .gantt .bar-activity .bar-progress { fill: #10b981; } /* Emerald */
    .gantt .bar-activity .bar { fill: #94a3b8; opacity: 0.3; }

    .gantt .bar-subactivity .bar-progress { fill: #0d9488; } /* Teal */
    .gantt .bar-subactivity .bar { fill: #99f6e4; opacity: 0.4; }

    /* Status Specific for Activities */
    .gantt .bar-activity.status-Done .bar-progress { fill: #059669; }
    .gantt .bar-activity.status-On-Progress .bar-progress { fill: #d97706; }
    .gantt .bar-activity.status-On-Hold .bar-progress { fill: #475569; }
    .gantt .bar-activity.status-Cancelled .bar-progress { fill: #be123c; }
    .gantt .bar-activity.status-To-Do .bar-progress { fill: #0ea5e9; }
    /* Status Specific for Sub Activities */
    .gantt .bar-subactivity.status-Done .bar-progress { fill: #0f766e; }
    .gantt .bar-subactivity.status-On-Progress .bar-progress { fill: #b45309; }
    .gantt .bar-subactivity.status-On-Hold .bar-progress { fill: #475569; }
    .gantt .bar-subactivity.status-Cancelled .bar-progress { fill: #9f1239; }
    .gantt .bar-subactivity.status-To-Do .bar-progress { fill: #0284c7; }
    
    /* View Mode Toggles Styling */
    .view-btn-grp .btn.active {
        background-color: #4338ca !important;
        color: white !important;
        border-color: #4338ca !important;
    }

    /* Resize Handles Improvement */
    .gantt .handle-group {
        opacity: 0;
        transition: opacity 0.2s;
    }
    .gantt .bar-wrapper:hover .handle-group {
        opacity: 1;
    }
    .gantt .handle {
        fill: #fff !important;
        stroke: #4338ca !important;
        stroke-width: 1.5 !important;
        rx: 3;
        ry: 3;
    }
    .gantt .bar-wrapper {
        cursor: pointer;
    }

</style>

<div class="d-flex flex-column gap-3">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="fw-bold fs-6 mb-0 text-dark">
            <i class="fa-solid fa-chart-gantt text-primary me-2"></i>Project Timeline
        </h5>
        <div class="btn-group btn-group-sm view-btn-grp shadow-sm" id="gantt-view-btns">
            <button onclick="changeGanttView('Day', this)" class="btn btn-outline-secondary">Day</button>
            <button onclick="changeGanttView('Week', this)" class="btn btn-outline-secondary">Week</button>
            <button onclick="changeGanttView('Month', this)" class="btn btn-primary active">Month</button>
        </div>
    </div>

    <div class="gantt-wrapper shadow-sm">
        <svg id="gantt-svg"></svg>
    </div>
</div>

{{-- MODAL UPDATE GANTT --}}
<div class="modal fade" id="ganttUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="ganttUpdateForm">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-2 d-flex align-items-center justify-content-center" id="gantt-modal-icon-bg" style="width: 36px; height: 36px; background: #eef2ff;">
                            <i class="fa-solid fa-edit" id="gantt-modal-icon" style="color: #4f46e5;"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;" id="gantt-modal-subtitle">Update Task</p>
                            <h5 class="fw-bold mb-0 fs-6" id="gantt-modal-title">Edit Timeline</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <input type="hidden" name="id" id="gantt-task-id">
                    <input type="hidden" name="type" id="gantt-task-type">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Item</label>
                        <input type="text" name="name" id="gantt-task-name" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Periode Tanggal <span class="text-danger">*</span></label>
                        <input type="text" id="gantt-date-range-picker" class="form-control form-control-sm" placeholder="Pilih tanggal mulai — selesai" readonly>
                        <input type="hidden" id="gantt-task-start" name="start">
                        <input type="hidden" id="gantt-task-end" name="end">
                    </div>
                    
                    <div id="gantt-progress-container">
                        <label class="form-label fw-semibold small">Progress (%)</label>
                        <div class="d-flex align-items-center gap-3 bg-light p-2 rounded-3 border">
                            <input type="range" class="form-range flex-grow-1" min="0" max="100" id="gantt-task-progress-range">
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <input type="number" name="progress" id="gantt-task-progress" class="form-control text-center fw-bold" min="0" max="100">
                                <span class="input-group-text px-1">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4 shadow-sm">
                        <i class="fa-solid fa-save me-1"></i> <span id="gantt-save-btn-text">Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    'use strict';
 
    // Ensure CustomSwal is available (theme matching show.blade.php)
    const CustomSwal = window.CustomSwal || Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-primary px-4 py-2 fw-semibold rounded-pill mx-2 shadow-sm',
            cancelButton:  'btn btn-light px-4 py-2 fw-semibold rounded-pill mx-2 border shadow-sm',
            title:         'fw-bold text-dark fs-5',
            popup:         'rounded-4 border-0 shadow-lg',
            actions:       'gap-2',
        },
        buttonsStyling: false,
    });

    // Flatpickr range picker for the Gantt update modal
    var ganttDateFp = flatpickr('#gantt-date-range-picker', {
        mode: 'range',
        dateFormat: 'd M Y',
        locale: { firstDayOfWeek: 1 },
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                var fmt = function(d) { return d.toISOString().split('T')[0]; };
                document.getElementById('gantt-task-start').value = fmt(selectedDates[0]);
                document.getElementById('gantt-task-end').value   = fmt(selectedDates[1]);
            } else {
                document.getElementById('gantt-task-start').value = '';
                document.getElementById('gantt-task-end').value   = '';
            }
        }
    });


    var rawTasks = {!! $ganttTasksJson !!};
    
    if (!rawTasks || rawTasks.length === 0) {
        document.getElementById('gantt-svg').innerHTML = '<text x="50%" y="50%" text-anchor="middle" fill="#94a3b8">No tasks available</text>';
        return;
    }

    var tasks = rawTasks.map(function(t) {
        var start = t.start || new Date().toISOString().split('T')[0];
        var end = t.end || start;
        
        // If start == end, add 1 day for rendering to prevent handle overlap
        if (start === end) {
            var d = new Date(start);
            d.setDate(d.getDate() + 1);
            end = d.toISOString().split('T')[0];
        }

        return {
            id: t.id,
            name: t.name,
            start: start,
            end: end,
            progress: t.progress || 0,
            dependencies: t.dependencies ? String(t.dependencies) : '',
            custom_class: t.custom_class || ''
        };
    });

    var gantt = new Gantt("#gantt-svg", tasks, {
        header_height: 50,
        column_width: 40,
        step: 24,
        view_modes: ['Day', 'Week', 'Month'],
        bar_height: 25,
        bar_corner_radius: 4,
        arrow_curve: 5,
        padding: 18,
        view_mode: 'Month',
        date_format: 'YYYY-MM-DD',
        language: 'en',
        on_date_change: function(task, start, end) {
            var start_str = start.toISOString().split('T')[0];
            var end_str = end.toISOString().split('T')[0];
            
            // Update local state
            var t = tasks.find(function(item) { return item.id === task.id; });
            if (t) {
                t.start = start_str;
                t.end = end_str;
                recalculateRollups();
                gantt.refresh(tasks);
                drawProgramRange();
            }
            
            updateGanttBackend(task.id, start, end, task.progress);
        },
        on_progress_change: function(task, progress) {
            // Update local state
            var t = tasks.find(function(item) { return item.id === task.id; });
            if (t) {
                t.progress = progress;
                gantt.refresh(tasks);
                drawProgramRange();
            }
            updateGanttBackend(task.id, task._start, task._end, progress);
        },
        on_click: function(task) {
            openGanttUpdateModal(task);
        }
    });

    // ---- Program date-range background highlight ----
    function drawProgramRange() {
        var svg = document.getElementById('gantt-svg');
        if (!svg) return;

        // Remove previous highlight
        var old = svg.querySelector('#prog-range-bg');
        if (old) old.remove();

        // Find the program bar's <rect class="bar"> in the SVG DOM
        var progTask = tasks.find(function(t) { return t.id.indexOf('prog_') === 0; });
        if (!progTask) return;

        // Frappe Gantt bar wrapper: <g data-id="prog_X">
        var barWrapper = svg.querySelector('[data-id="' + progTask.id + '"]');
        if (!barWrapper) return;
        var barRect = barWrapper.querySelector('rect.bar');
        if (!barRect) return;

        var x1 = parseFloat(barRect.getAttribute('x'));
        var barW = parseFloat(barRect.getAttribute('width'));
        var x2 = x1 + barW;
        if (barW <= 0) return;

        // Use the SVG's actual viewBox height or a large fallback
        var svgH = parseInt(svg.getAttribute('height') || svg.getBBox ? 0 : 2000);
        try { svgH = svg.getBBox().height || 2000; } catch(e) { svgH = 2000; }

        var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('id', 'prog-range-bg');

        // Shaded band inside program range
        var band = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        band.setAttribute('x',      x1);
        band.setAttribute('y',      0);
        band.setAttribute('width',  barW);
        band.setAttribute('height', svgH);
        band.setAttribute('fill',   'rgba(99,102,241,0.06)');
        band.setAttribute('pointer-events', 'none');
        g.appendChild(band);

        // Vertical start line (indigo dashed)
        var ls = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        ls.setAttribute('x1', x1); ls.setAttribute('y1', 0);
        ls.setAttribute('x2', x1); ls.setAttribute('y2', svgH);
        ls.setAttribute('stroke', 'rgba(99,102,241,0.55)');
        ls.setAttribute('stroke-width', '2');
        ls.setAttribute('stroke-dasharray', '5,3');
        ls.setAttribute('pointer-events', 'none');
        g.appendChild(ls);

        // Vertical end line (red dashed)
        var le = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        le.setAttribute('x1', x2); le.setAttribute('y1', 0);
        le.setAttribute('x2', x2); le.setAttribute('y2', svgH);
        le.setAttribute('stroke', 'rgba(239,68,68,0.55)');
        le.setAttribute('stroke-width', '2');
        le.setAttribute('stroke-dasharray', '5,3');
        le.setAttribute('pointer-events', 'none');
        g.appendChild(le);

        // "End" label near top of end line
        var lbl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        lbl.setAttribute('x', x2 + 3);
        lbl.setAttribute('y', 62);
        lbl.setAttribute('fill', 'rgba(239,68,68,0.8)');
        lbl.setAttribute('font-size', '9');
        lbl.setAttribute('pointer-events', 'none');
        lbl.textContent = 'End';
        g.appendChild(lbl);

        // "Start" label near top of start line
        var lblS = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        lblS.setAttribute('x', x1 + 3);
        lblS.setAttribute('y', 62);
        lblS.setAttribute('fill', 'rgba(99,102,241,0.8)');
        lblS.setAttribute('font-size', '9');
        lblS.setAttribute('pointer-events', 'none');
        lblS.textContent = 'Start';
        g.appendChild(lblS);

        // Insert BEFORE the bars group so we're above grid-background but below bars
        var barsGroup = svg.querySelector('g.bars');
        if (barsGroup) {
            svg.insertBefore(g, barsGroup);
        } else {
            svg.appendChild(g); // fallback: append last (with pointer-events:none it's safe)
        }
    }

    // Wait for Frappe Gantt to finish rendering before drawing overlay
    setTimeout(drawProgramRange, 300);

    function recalculateRollups() {
        // 0. Activities from Sub Activities
        tasks.filter(t => t.id.startsWith('act_')).forEach(act => {
            let children = tasks.filter(t => t.dependencies === act.id);
            if (children.length > 0) {
                let min = null, max = null;
                children.forEach(c => {
                    if (!min || c.start < min) min = c.start;
                    if (!max || c.end > max) max = c.end;
                });
                act.start = min;
                act.end = max;
            }
        });

        // 1. Milestones from Activities
        tasks.filter(t => t.id.startsWith('ms_')).forEach(ms => {
            let children = tasks.filter(t => t.dependencies === ms.id);
            if (children.length > 0) {
                let min = null, max = null;
                children.forEach(c => {
                    if (!min || c.start < min) min = c.start;
                    if (!max || c.end > max) max = c.end;
                });
                ms.start = min;
                ms.end = max;
            }
        });

        // 2. Sub Programs from Milestones
        tasks.filter(t => t.id.startsWith('sub_')).forEach(sub => {
            let children = tasks.filter(t => t.dependencies === sub.id);
            if (children.length > 0) {
                let min = null, max = null;
                children.forEach(c => {
                    if (!min || c.start < min) min = c.start;
                    if (!max || c.end > max) max = c.end;
                });
                sub.start = min;
                sub.end = max;
            }
        });

        // 3. Program from Sub Programs
        tasks.filter(t => t.id.startsWith('prog_')).forEach(prog => {
            let children = tasks.filter(t => t.dependencies === prog.id);
            if (children.length > 0) {
                let min = null, max = null;
                children.forEach(c => {
                    if (!min || c.start < min) min = c.start;
                    if (!max || c.end > max) max = c.end;
                });
                prog.start = min;
                prog.end = max;
            }
        });
    }

    function updateGanttBackend(rawId, start, end, progress) {
        var start_str = (start instanceof Date) ? start.toISOString().split('T')[0] : start;
        var end_str = (end instanceof Date) ? end.toISOString().split('T')[0] : end;
        
        console.log("Saving task:", { id: rawId, start: start_str, end: end_str, progress: progress });

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error("CSRF token meta tag not found!");
            CustomSwal.fire({
                icon: 'error',
                title: 'Security Error',
                text: 'CSRF token missing.',
            });
            return Promise.reject("CSRF missing");
        }

        return fetch('/api/projects/gantt/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                id: rawId, // Use prefixed ID (e.g. prog_1) to match controller strpos
                start: start_str,
                end: end_str,
                progress: progress
            })
        }).then(function(res) {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error("Server Error ("+res.status+"): " + text);
                });
            }
            return res.json();
        });
    }

    // Modal Handling
    var $modal = new bootstrap.Modal(document.getElementById('ganttUpdateModal'));
    var $form = document.getElementById('ganttUpdateForm');

    function openGanttUpdateModal(task) {
        document.getElementById('gantt-task-id').value = task.id;
        document.getElementById('gantt-task-name').value = task.name;
        document.getElementById('gantt-task-start').value = task.start;
        document.getElementById('gantt-task-end').value = task.end;
        document.getElementById('gantt-task-progress').value = task.progress;
        document.getElementById('gantt-task-progress-range').value = task.progress;
        // Set the visual Flatpickr range picker
        if (task.start && task.end) {
            ganttDateFp.setDate([task.start, task.end], false, 'Y-m-d');
        } else {
            ganttDateFp.clear();
        }
        
        // Dynamic Styling
        const iconBg = document.getElementById('gantt-modal-icon-bg');
        const icon = document.getElementById('gantt-modal-icon');
        const subtitle = document.getElementById('gantt-modal-subtitle');
        const title = document.getElementById('gantt-modal-title');
        
        var isActivity = task.id.indexOf('act_') === 0;
        var isSubAct = task.id.indexOf('subact_') === 0;
        var isMilestone = task.id.indexOf('ms_') === 0;
        var isSub = task.id.indexOf('sub_') === 0;
        var isProg = task.id.indexOf('prog_') === 0;

        if (isProg) {
            iconBg.style.background = '#eef2ff'; icon.style.color = '#4f46e5'; icon.className = 'fa-solid fa-diagram-project';
            subtitle.innerText = 'Project Level'; title.innerText = 'Edit Program';
        } else if (isSub) {
            iconBg.style.background = '#eef2ff'; icon.style.color = '#4f46e5'; icon.className = 'fa-solid fa-diagram-project';
            subtitle.innerText = 'Sub Program'; title.innerText = 'Edit Timeline';
        } else if (isMilestone) {
            iconBg.style.background = '#dbeafe'; icon.style.color = '#3b82f6'; icon.className = 'fa-solid fa-flag-checkered';
            subtitle.innerText = 'Milestone'; title.innerText = 'Edit Timeline';
        } else if (isSubAct) {
            iconBg.style.background = '#ccfbf1'; icon.style.color = '#0d9488'; icon.className = 'fa-solid fa-circle-nodes';
            subtitle.innerText = 'Sub Activity'; title.innerText = 'Edit Timeline';
        } else if (isActivity) {
            iconBg.style.background = '#d1fae5'; icon.style.color = '#059669'; icon.className = 'fa-solid fa-list-check';
            subtitle.innerText = 'Activity'; title.innerText = 'Edit Timeline';
        }

        document.getElementById('gantt-progress-container').style.display = (isActivity || isSubAct) ? 'block' : 'none';
        
        $modal.show();
    }

    // Sync progress input and range
    document.getElementById('gantt-task-progress-range').oninput = function() {
        document.getElementById('gantt-task-progress').value = this.value;
    };
    document.getElementById('gantt-task-progress').oninput = function() {
        document.getElementById('gantt-task-progress-range').value = this.value;
    };

    $form.onsubmit = function(e) {
        e.preventDefault();
        var btnText = document.getElementById('gantt-save-btn-text');
        var originalText = btnText.textContent;
        btnText.textContent = 'Saving...';

        var id = document.getElementById('gantt-task-id').value;
        var start = document.getElementById('gantt-task-start').value;
        var end = document.getElementById('gantt-task-end').value;
        var progress = document.getElementById('gantt-task-progress').value;

        updateGanttBackend(id, start, end, progress)
            .then(function(data) {
                if (data.success) {
                    // Update local tasks array and refresh gantt
                    var task = tasks.find(function(t) { return t.id === id; });
                    if (task) {
                        task.start = start;
                        task.end = end;
                        task.progress = parseInt(progress);
                        recalculateRollups();
                        gantt.refresh(tasks);
                    }
                    $modal.hide();
                } else {
                    CustomSwal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: (data.message || 'Failed to update'),
                    });
                }
            })
            .catch(function(err) {
                console.error(err);
                CustomSwal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Connection error',
                });
            })
            .finally(function() {
                btnText.textContent = originalText;
            });
    };

    window.changeGanttView = function(mode, btn) {
        gantt.change_view_mode(mode);
        document.querySelectorAll('#gantt-view-btns .btn').forEach(function(b) {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary', 'active');
    };

})();
</script>
