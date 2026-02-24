<!-- CSS for FullCalendar -->
<style>
    #calendar {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 0;
    }
</style>

<div class="d-flex flex-column gap-3">
    <div class="d-flex justify-content-between align-items-center mb-0">
        <h5 class="fw-bold fs-6 mb-0 text-dark"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Activity Calendar</h5>
    </div>
    
    <!-- Calendar Container -->
    <div id="calendar" class="shadow-sm border border-light-subtle"></div>
</div>

<!-- JS for FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    (function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,listWeek'
            },
            events: '/api/projects/calendar?program_id={{ $program->id }}',
            eventDidMount: function(info) {
                info.el.title = "Status: " + info.event.extendedProps.status.toUpperCase() + " | Progress: " + info.event.extendedProps.progress + "%";
            }
        });

        calendar.render();
    })();
</script>
