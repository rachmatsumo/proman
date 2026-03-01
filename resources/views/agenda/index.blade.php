@extends('layouts.app')

@section('title', 'Agenda - ProMan')
@section('header_title', 'Agenda & Scheduling')

@section('header_actions')
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-success btn-sm shadow-sm rounded-pill px-3 d-flex align-items-center" onclick="copyWAReport()">
            <i class="fa-brands fa-whatsapp me-md-1"></i> <span class="d-none d-md-inline">Copy for WA</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm shadow-sm rounded-pill px-3 d-flex align-items-center" onclick="openCreateModal()">
            <i class="fa-solid fa-plus me-md-1"></i> <span class="d-none d-md-inline">New Agenda</span>
        </button>
    </div>
@endsection

@section('content')
<div class="container-fluid py-3 py-md-4 h-100 overflow-auto">
    <div class="row g-3 g-md-4 h-100">
        <!-- Calendar/Date Picker Sidebar -->
        <div class="col-lg-3 flex-shrink-0">
            <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                <div class="card-body p-3 p-md-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-calendar-day text-primary me-2"></i> Select Date
                    </h6>
                    <input type="date" id="agenda-date-picker" class="form-control border-light shadow-none bg-light rounded-3" value="{{ $date }}" onchange="changeDate(this.value)">
                    
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3 small text-uppercase text-muted" style="letter-spacing: 0.1em;">Statistics</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">Total Tasks</span>
                            <span class="badge bg-primary rounded-pill">{{ $agendas instanceof \Illuminate\Support\Collection ? $agendas->flatten()->count() : $agendas->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Completed</span>
                            <span class="badge bg-success rounded-pill">{{ ($agendas instanceof \Illuminate\Support\Collection ? $agendas->flatten() : $agendas)->where('status', 'Done')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden position-relative d-none d-lg-block" style="min-height: 150px;">
                 <div class="card-body p-4 z-1 position-relative">
                    <h5 class="fw-bold mb-1">Stay Organized</h5>
                    <p class="small mb-0">Track your hourly activities and manage multiple PICs efficiently.</p>
                 </div>
                 <i class="fa-solid fa-clock position-absolute bottom-0 end-0 opacity-75" style="font-size: 8rem; transform: translate(20%, 20%);"></i>
            </div>
        </div>

        <!-- Timeline/Weekly View -->
        <div class="col-lg-9 h-100 d-flex flex-column">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1 overflow-hidden d-flex flex-column" style="min-height: 500px;">
                <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="text-center text-md-start">
                        <h5 class="fw-bold mb-0">
                            @if($view === 'weekly')
                                {{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                            @endif
                        </h5>
                        <p class="small text-muted mb-0">{{ $view === 'weekly' ? 'Weekly Overview' : 'Hourly Timeline' }}</p>
                    </div>
                    <div class="d-flex align-items-center justify-content-center gap-2 gap-md-3">
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill flex-shrink-0">
                            <button class="btn {{ $view === 'daily' ? 'btn-white shadow-sm' : 'btn-light border-0' }} rounded-pill px-3 fw-bold" 
                                    onclick="switchView('daily')" style="font-size: 0.65rem;">Daily</button>
                            <button class="btn {{ $view === 'weekly' ? 'btn-white shadow-sm' : 'btn-light border-0' }} rounded-pill px-3 fw-bold" 
                                    onclick="switchView('weekly')" style="font-size: 0.65rem;">Weekly</button>
                        </div>
                        <div class="btn-group btn-group-sm flex-shrink-0">
                            <button class="btn btn-outline-light text-dark border-light-subtle shadow-none" 
                                    onclick="changeDate('{{ $view === 'weekly' ? $startOfWeek->copy()->subWeek()->format('Y-m-d') : \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}')">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-outline-light text-dark border-light-subtle shadow-none px-2" onclick="changeDate('{{ \Carbon\Carbon::today()->format('Y-m-d') }}')">Today</button>
                            <button class="btn btn-outline-light text-dark border-light-subtle shadow-none" 
                                    onclick="changeDate('{{ $view === 'weekly' ? $startOfWeek->copy()->addWeek()->format('Y-m-d') : \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}')">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0 overflow-auto position-relative" id="timeline-container" style="background: #fdfdfd;">
                    @if($view === 'weekly')
                        <!-- Weekly Grid View -->
                        <div class="weekly-grid d-flex h-100" style="min-width: 1000px;">
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $current = $startOfWeek->copy()->addDays($i);
                                    $dateStr = $current->format('Y-m-d');
                                    $isToday = $current->isToday();
                                    $dayAgendas = $agendas[$dateStr] ?? collect();
                                @endphp
                                <div class="weekly-day-col flex-grow-1 border-end d-flex flex-column {{ $isToday ? 'bg-primary-subtle bg-opacity-10' : '' }}" style="min-width: 142px;">
                                    <div class="day-header p-3 text-center border-bottom {{ $isToday ? 'bg-primary text-white' : 'bg-light' }}">
                                        <div class="small fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ $current->format('D') }}</div>
                                        <div class="h5 mb-0 fw-black">{{ $current->format('d') }}</div>
                                    </div>
                                    <div class="day-content p-2 flex-grow-1 overflow-auto" style="background: repeating-linear-gradient(rgba(0,0,0,0.02) 0px, rgba(0,0,0,0.02) 1px, transparent 1px, transparent 40px);">
                                        @foreach($dayAgendas as $agenda)
                                            <div class="agenda-item-sm p-2 mb-2 rounded-3 border-start border-3 shadow-sm transition-all {{ $agenda->status }}" 
                                                 style="background: white; cursor: pointer; font-size: 0.72rem;"
                                                 onclick="openEditModal({{ json_encode($agenda) }})">
                                                <div class="fw-bold text-truncate mb-1">{{ $agenda->title }}</div>
                                                <div class="d-flex justify-content-between align-items-center opacity-75" style="font-size: 0.65rem;">
                                                    <span>{{ \Carbon\Carbon::parse($agenda->start_time)->format('H:i') }}</span>
                                                    <span class="badge {{ $agenda->status === 'Done' ? 'bg-success' : ($agenda->status === 'Cancelled' ? 'bg-danger' : 'bg-primary') }} rounded-pill" style="font-size: 0.5rem; padding: 0.2em 0.5em;">{{ $agenda->status }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="text-center mt-2">
                                            <button class="btn btn-outline-primary btn-sm rounded-circle p-0" style="width: 24px; height: 24px; font-size: 0.7rem;" onclick="openCreateModalAt('{{ $dateStr }}')">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    @else
                        <!-- Hourly Grid View (Original) -->
                        @php
                            $hourStart = 7;
                            $hourEnd = 19;
                        @endphp
                        <div class="timeline-grid position-relative" style="min-height: {{ ($hourEnd - $hourStart + 1) * 60 }}px;">
                            @for($i = $hourStart; $i <= $hourEnd; $i++)
                                @php $time = sprintf('%02d:00', $i); @endphp
                                <div class="hour-row d-flex align-items-start border-bottom position-relative" style="height: 60px;">
                                    <div class="hour-label text-muted small fw-bold px-4 py-2" style="width: 100px; font-size: 0.75rem;">
                                        {{ $time }}
                                    </div>
                                    <div class="hour-lane flex-grow-1 h-100 position-relative"></div>
                                </div>
                            @endfor

                            <!-- Agenda Items -->
                            <div class="agenda-overlay position-absolute top-0 start-0 w-100 h-100" style="padding-left: 100px; pointer-events: none;">
                                @php
                                    $processed = [];
                                    $offsetMinutes = $hourStart * 60;
                                @endphp
                                @foreach($agendas as $agenda)
                                    @php
                                        $start = \Carbon\Carbon::parse($agenda->start_time);
                                        $end = $agenda->end_time ? \Carbon\Carbon::parse($agenda->end_time) : $start->copy()->addHour();
                                        
                                        $startMins = ($start->hour * 60) + $start->minute;
                                        $endMins = ($end->hour * 60) + $end->minute;

                                        // Calc lane for overlapping
                                        $lane = 0;
                                        foreach ($processed as $p) {
                                            if ($startMins < $p['end'] && $endMins > $p['start']) {
                                                $lane++;
                                            }
                                        }
                                        $processed[] = ['start' => $startMins, 'end' => $endMins];

                                        $top = $startMins - $offsetMinutes;
                                        $height = max(40, $endMins - $startMins);
                                        $left = 20 + ($lane * 40); // Shift 40px right per overlap
                                        $width = max(30, 80 - ($lane * 10)); // Narrow slightly
                                        
                                        // Skip if totally outside the visible range
                                        if ($endMins < $offsetMinutes || $startMins > ($hourEnd + 1) * 60) {
                                            continue;
                                        }
                                    @endphp
                                    <div class="agenda-card position-absolute rounded-3 border-start border-4 shadow-sm p-3 transition-all {{ $agenda->status }}" 
                                         style="top: {{ $top }}px; height: {{ $height }}px; left: {{ $left }}px; width: {{ $width }}%; z-index: {{ 10 + $lane }}; cursor: pointer; pointer-events: auto; opacity: 0.95;"
                                         onclick="openEditModal({{ json_encode($agenda) }})">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="fw-bold small text-truncate">{{ $agenda->title }}</div>
                                            <div class="small opacity-75" style="font-size: 0.7rem;">
                                                <i class="fa-regular fa-clock me-1"></i>{{ $start->format('H:i') }} - {{ $end->format('H:i') }}
                                            </div>
                                        </div>
                                        @if($height > 50)
                                            <div class="small opacity-75 text-truncate mb-1" style="font-size: 0.7rem;">
                                                @if($agenda->location)
                                                    <i class="fa-solid fa-location-dot me-1"></i>{{ $agenda->location }}
                                                @endif
                                                @if($agenda->uic)
                                                    <i class="fa-solid fa-building ms-2 me-1"></i>{{ $agenda->uic }}
                                                @endif
                                                @if(!$agenda->location && !$agenda->uic)
                                                    <i class="fa-solid fa-circle-info me-1"></i>No details
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1 overflow-hidden">
                                                @foreach($agenda->pics as $pic)
                                                    <div class="rounded-circle bg-white border d-flex align-items-center justify-content-center fw-bold" 
                                                         style="width: 18px; height: 18px; font-size: 8px;" title="{{ $pic->name }}">
                                                        {{ substr($pic->name, 0, 1) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="agendaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="modal-title">Create New Agenda</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="agendaForm">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                <input type="hidden" name="id" id="agenda-id">
                <input type="hidden" name="date" id="agenda-date" value="{{ $date }}">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" name="title" id="form-title" class="form-control border-light shadow-none bg-light rounded-3" placeholder="e.g. Weekly Sync Meeting" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="form-description" class="form-control border-light shadow-none bg-light rounded-3" rows="2" placeholder="Tasks or topics..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Start Time</label>
                            <input type="time" name="start_time" id="form-start-time" class="form-control border-light shadow-none bg-light rounded-3" value="09:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">End Time</label>
                            <input type="time" name="end_time" id="form-end-time" class="form-control border-light shadow-none bg-light rounded-3" value="10:00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Location</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light rounded-start-3"><i class="fa-solid fa-location-dot text-muted"></i></span>
                                <input type="text" name="location" id="form-location" class="form-control border-light shadow-none bg-light rounded-end-3" placeholder="Room A">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">UIC (Unit In Charge)</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light rounded-start-3"><i class="fa-solid fa-building text-muted"></i></span>
                                <input type="text" name="uic" id="form-uic" class="form-control border-light shadow-none bg-light rounded-end-3" placeholder="Finance/IT">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="meeting-id-container">
                        <label class="form-label small fw-bold">Meeting ID (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light rounded-start-3"><i class="fa-solid fa-hashtag text-muted"></i></span>
                            <input type="text" name="meeting_id" id="form-meeting-id" class="form-control border-light shadow-none bg-light rounded-end-3" placeholder="Meeting ID / Password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="form-status" class="form-select border-light shadow-none bg-light rounded-3">
                            <option value="Pending">Pending</option>
                            <option value="Done">Done</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold d-block">PICs (Multiple)</label>
                        <div class="pic-selector bg-light rounded-3 p-3" style="max-height: 150px; overflow-y: auto;">
                            @foreach($users as $user)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="pics[]" value="{{ $user->id }}" id="pic-{{ $user->id }}">
                                    <label class="form-check-label small" for="pic-{{ $user->id }}">
                                        {{ $user->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 shadow-none" id="delete-btn" style="display: none;" onclick="deleteAgenda()">
                        <i class="fa-solid fa-trash me-1"></i> Delete
                    </button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white" id="submit-btn">Save Agenda</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .agenda-card {
        border-start-width: 4px !important;
        background: white !important;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .agenda-card:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        z-index: 100 !important;
    }
    
    /* Dynamic Status Styles */
    .agenda-card.Pending { border-left-color: #4338ca !important; }
    .agenda-card.Done { border-left-color: #10b981 !important; opacity: 0.8; }
    .agenda-card.Cancelled { border-left-color: #ef4444 !important; opacity: 0.6; text-decoration: line-through; }
    
    .nav-link.active {
        background-color: var(--primary-color) !important;
        color: white !important;
    }
</style>

<script>
    function getAgendaModal() {
        var el = document.getElementById('agendaModal');
        return bootstrap.Modal.getOrCreateInstance(el);
    }

    function changeDate(date) {
        const view = '{{ $view }}';
        window.location.href = `{{ route('agendas.index') }}?date=${date}&view=${view}`;
    }

    function switchView(view) {
        const date = '{{ $date }}';
        window.location.href = `{{ route('agendas.index') }}?date=${date}&view=${view}`;
    }

    function openCreateModal() {
        document.getElementById('modal-title').innerText = 'Create New Agenda';
        document.getElementById('form-method').value = 'POST';
        document.getElementById('agenda-id').value = '';
        document.getElementById('agenda-date').value = '{{ $date }}';
        document.getElementById('delete-btn').style.display = 'none';
        
        var form = document.getElementById('agendaForm');
        if (form) form.reset();
        
        // Uncheck all PICs
        document.querySelectorAll('input[name="pics[]"]').forEach(cb => cb.checked = false);
        
        getAgendaModal().show();
    }

    function openCreateModalAt(date) {
        openCreateModal();
        document.getElementById('agenda-date').value = date;
    }

    function openEditModal(agenda) {
        document.getElementById('modal-title').innerText = 'Edit Agenda';
        document.getElementById('form-method').value = 'PUT';
        document.getElementById('agenda-id').value = agenda.id;
        document.getElementById('agenda-date').value = agenda.date;
        document.getElementById('delete-btn').style.display = 'block';
        
        document.getElementById('form-title').value = agenda.title;
        document.getElementById('form-description').value = agenda.description;
        document.getElementById('form-start-time').value = agenda.start_time.substring(0, 5);
        document.getElementById('form-end-time').value = agenda.end_time ? agenda.end_time.substring(0, 5) : '';
        document.getElementById('form-location').value = agenda.location;
        document.getElementById('form-uic').value = agenda.uic || '';
        document.getElementById('form-meeting-id').value = agenda.meeting_id || '';
        document.getElementById('form-status').value = agenda.status;
        
        // Set PIC checks
        document.querySelectorAll('input[name="pics[]"]').forEach(cb => {
            cb.checked = agenda.pics.some(p => p.id == cb.value);
        });
        
        getAgendaModal().show();
    }

    function deleteAgenda() {
        const id = document.getElementById('agenda-id').value;
        if (!id || !confirm('Are you sure you want to delete this agenda?')) return;

        fetch(`{{ url("agendas") }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => console.error(err));
    }

    function copyWAReport() {
        const agendas = {!! json_encode($agendas) !!};
        if (agendas.length === 0) {
            alert('No agenda items for this date.');
            return;
        }

        const dateStr = '{{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM YYYY') }}';
        let report = `Assalamu’alaikum warahmatullahi wabarakatuh,\n`;
        report += `Selamat Malam Bapak GH, Para Division Head, serta Tim DOP.\n`;
        report += `Izin menyampaikan Update Agenda rapat pada hari ${dateStr} sebagai berikut:\n\n`;

        agendas.forEach((item, index) => {
            const start = item.start_time.substring(0, 5);
            const end = item.end_time ? item.end_time.substring(0, 5) : 'selesai';
            const pics = item.pics.map(p => p.name).join(', ') || '-';
            
            report += `${index + 1}. Agenda : ${item.title}\n`;
            report += `Waktu : ${start} - ${end} WIB\n`;
            report += `PIC : ${pics}\n`;
            report += `Disposisi : ${item.uic || '-'}\n`;
            report += `Tempat : ${item.location || '-'}\n`;
            if (item.meeting_id) {
                report += `Meeting ID : ${item.meeting_id}\n`;
            }
            report += `\n`;
        });

        report += `Demikian disampaikan. Apabila terdapat agenda yang belum tercantum, mohon dapat diinformasikan.\n`;
        report += `Terima kasih🙏`;

        navigator.clipboard.writeText(report).then(() => {
            alert('Report copied to clipboard! You can now paste it directly into WhatsApp.');
        });
    }

    document.getElementById('agendaForm').onsubmit = function(e) {
        e.preventDefault();
        
        const id = document.getElementById('agenda-id').value;
        const method = document.getElementById('form-method').value;
        const url = method === 'POST' ? '{{ route("agendas.store") }}' : `{{ url("agendas") }}/${id}`;
        
        const formData = new FormData(this);
        
        fetch(url, {
            method: 'POST', // Use POST with _method spoofing for PUT
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Validation error: ' + JSON.stringify(data.errors));
            }
        })
        .catch(err => console.error(err));
    }

    // Scroll to current hour on load
    window.onload = function() {
        const view = '{{ $view }}';
        if (view === 'daily') {
            const now = new Date();
            const hour = now.getHours();
            const container = document.getElementById('timeline-container');
            if (container) {
                // Adjust scroll based on 07:00 start (if hour is < 7, scroll to 0)
                const scrollHour = Math.max(0, hour - 7);
                container.scrollTop = Math.max(0, scrollHour * 60 - 50);
            }
        }
    }
</script>

<style>
    .weekly-grid {
        background: #fdfdfd;
        overflow-x: auto;
    }
    .weekly-day-col {
        min-height: 400px;
        transition: background-color 0.2s;
    }
    .weekly-day-col:hover {
        background-color: rgba(67, 56, 202, 0.02);
    }
    .day-header {
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .agenda-item-sm {
        transition: transform 0.1s;
    }
    .agenda-item-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08) !important;
    }
    .agenda-item-sm.Pending { border-left-color: #4338ca !important; }
    .agenda-item-sm.Done { border-left-color: #10b981 !important; opacity: 0.8; }
    .agenda-item-sm.Cancelled { border-left-color: #ef4444 !important; opacity: 0.6; text-decoration: line-through; }
    
    .fw-black { font-weight: 900; }
    .btn-white { background-color: white !important; }
</style>
@endsection
