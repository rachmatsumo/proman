{{-- Activity Calendar Partial — loaded via AJAX, so CDN links are injected inline --}}

{{-- FullCalendar CSS (injected inline so it works in AJAX-loaded tabs) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" />

<style>
    #fc-calendar-wrap {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 2px 16px rgba(99,102,241,0.06);
    }
    @media (max-width: 767.98px) {
        .fc .fc-toolbar-title { font-size: 0.9rem; }
        .fc .fc-toolbar { flex-direction: column; gap: 10px; }
        .fc .fc-toolbar-chunk { display: flex; justify-content: center; width: 100%; }
        .fc-event { font-size: 0.65rem; padding: 2px 4px; }
    }
    .fc-event { border-radius: 6px !important; font-size: 0.72rem; padding: 2px 6px; cursor: pointer; border: none !important; margin-bottom: 1px !important; transition: transform 0.1s; }
    .fc-event:hover { transform: scale(1.02); filter: brightness(0.95); }
    .fc-event-title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fc-legend { display: flex; gap: 12px; flex-wrap: wrap; font-size: 0.72rem; color: #64748b; align-items: center; }
    .fc-legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }
    /* Remove underlines on date numbers and day column headers */
    .fc .fc-daygrid-day-number,
    .fc .fc-col-header-cell-cushion,
    .fc a { text-decoration: none !important; }
</style>

<div class="d-flex flex-column gap-3">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold fs-6 mb-0 text-dark">
                <i class="fa-solid fa-calendar-days text-primary me-2"></i>Activity Calendar
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Semua activity program ditampilkan per status</p>
        </div>
        {{-- Legend --}}
        {{-- Legend --}}
        <div class="fc-legend" id="calendarLegend">
            <div class="d-flex align-items-center"><span class="fc-legend-dot" style="background:#059669;"></span>Done</div>
            <div class="d-flex align-items-center"><span class="fc-legend-dot" style="background:#d97706;"></span>On Progress</div>
            <div class="d-flex align-items-center"><span class="fc-legend-dot" style="background:#0ea5e9;"></span>To Do</div>
            <div class="d-flex align-items-center"><span class="fc-legend-dot" style="background:#475569;"></span>On Hold</div>
            <div class="d-flex align-items-center"><span class="fc-legend-dot" style="background:#be123c;"></span>Cancelled</div>
        </div>
    </div>

    <style>
        @media (max-width: 575.98px) {
            .fc-legend { gap: 8px 12px; }
            .fc-legend > div { font-size: 0.65rem; }
            #fc-calendar-wrap { padding: 0.75rem !important; }
        }
    </style>

    {{-- Calendar --}}
    <div id="fc-calendar-wrap" class="p-3">
        <div id="fc-calendar"></div>
    </div>
</div>

{{-- Event Detail Popover --}}
<div id="fc-event-popover"
     style="display:none; position:fixed; z-index:9999; background:white; border-radius:12px;
            box-shadow:0 8px 32px rgba(0,0,0,0.15); padding:14px 18px; min-width:220px; border:1px solid #e2e8f0;">
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div>
            <p class="fw-bold mb-1 text-dark" id="pop-title" style="font-size:0.85rem;"></p>
            <span id="pop-status" class="badge rounded-pill mb-2" style="font-size:0.65rem;"></span>
            <div class="d-flex flex-column gap-1 text-muted" style="font-size:0.72rem;">
                <div><i class="fa-regular fa-calendar me-1 text-primary"></i><span id="pop-dates"></span></div>
                <div id="pop-progress-wrap">
                    <i class="fa-solid fa-circle-half-stroke me-1 text-success"></i>
                    Progress: <strong id="pop-progress"></strong>
                </div>
            </div>
        </div>
        <button onclick="document.getElementById('fc-event-popover').style.display='none'"
                style="background:none;border:none;font-size:1rem;color:#94a3b8;cursor:pointer;line-height:1;">&times;</button>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initCalendar() {
        var calEl = document.getElementById('fc-calendar');
        if (!calEl) return;

        var statusColors = {
            'Done':        '#059669',
            'On Progress': '#d97706',
            'To Do':       '#0ea5e9',
            'On Hold':     '#475569',
            'Cancelled':   '#be123c',
        };

        var isMobile = window.innerWidth < 768;
        var cal = new FullCalendar.Calendar(calEl, {
            initialView: isMobile ? 'listWeek' : 'dayGridMonth',
            height: 'auto',
            dayMaxEvents: isMobile ? 2 : 3, // Very important: limits events per day
            headerToolbar: isMobile ? {
                left:   'prev,next',
                center: 'title',
                right:  'today,listWeek'
            } : {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,dayGridWeek,listWeek'
            },
            buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', list: 'List' },
            locale: 'id',
            events: function(info, successCb, failureCb) {
                fetch('/api/projects/calendar?program_id={{ $program->id }}&start=' + info.startStr + '&end=' + info.endStr)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var events = data.map(function(e) {
                            var status = e.extendedProps ? e.extendedProps.status : (e.status || '');
                            var color  = statusColors[status] || '#6366f1';
                            return {
                                id:    e.id,
                                title: e.title,
                                start: e.start,
                                end:   e.end,
                                backgroundColor: color,
                                borderColor:     color,
                                extendedProps:   e.extendedProps || { status: status, progress: e.progress || 0 }
                            };
                        });
                        successCb(events);
                    })
                    .catch(failureCb);
            },
            eventClick: function(info) {
                var ev     = info.event;
                var pop    = document.getElementById('fc-event-popover');
                var status = ev.extendedProps.status || '-';
                var prog   = ev.extendedProps.progress || 0;
                var color  = statusColors[status] || '#6366f1';

                document.getElementById('pop-title').textContent    = ev.title;
                document.getElementById('pop-status').textContent   = status;
                document.getElementById('pop-status').style.background = color;
                document.getElementById('pop-status').style.color      = '#fff';
                document.getElementById('pop-progress').textContent = prog + '%';
                document.getElementById('pop-dates').textContent =
                    (ev.startStr || '').substring(0,10) + ' → ' + (ev.endStr || '').substring(0,10);

                var rect = info.el.getBoundingClientRect();
                pop.style.left = (rect.left + window.scrollX) + 'px';
                pop.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
                pop.style.display = 'block';
                info.jsEvent.stopPropagation();
            },
            eventMouseLeave: function() {
                // close popover after short delay (allow hover to popover itself)
            }
        });

        cal.render();

        // Hide popover on outside click
        document.addEventListener('click', function() {
            document.getElementById('fc-event-popover').style.display = 'none';
        });

        // Re-size when tab becomes visible (Bootstrap shown.bs.tab event)
        document.addEventListener('shown.bs.tab', function(e) {
            if (e.target && e.target.id === 'calendar-tab') {
                cal.updateSize();
            }
        });
    }

    // Load FullCalendar JS dynamically if not already present (AJAX partial)
    if (typeof FullCalendar !== 'undefined') {
        initCalendar();
    } else {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
        script.onload = initCalendar;
        document.head.appendChild(script);
    }
})();
</script>
