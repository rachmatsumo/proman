@php
    $months = [];
    foreach($weeklyPeriods as $wp) {
        $months[$wp['month_label']][] = $wp;
    }
@endphp

<div class="d-flex flex-column gap-4">
    {{-- CHART CARD --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i>S-Curve Analysis</h5>
                <p class="text-muted mb-0 small">Grafik Kemajuan Direncanakan vs Realisasi</p>
            </div>
            <div class="d-flex gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-pill" style="width: 12px; height: 12px; background: #6366f1;"></span>
                    <span class="small fw-semibold text-muted">Planned (PV)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-pill" style="width: 12px; height: 12px; background: #10b981;"></span>
                    <span class="small fw-semibold text-muted">Actual (AV)</span>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div style="height: 350px; position: relative;">
                <canvas id="scurveChart"></canvas>
            </div>
        </div>
        <div class="card-footer bg-light border-0 py-3 px-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded-3 border">
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Realisasi Komulatif</p>
                        <h4 class="fw-black mb-0 text-success">{{ end($avData) ?? 0 }}%</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded-3 border">
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Rencana Komulatif</p>
                        <h4 class="fw-black mb-0" style="color: #6366f1;">
                            @php
                                $todayIdx = 0;
                                $today = now()->startOfDay();
                                foreach($labels as $idx => $label) {
                                    $d = \Carbon\Carbon::createFromFormat('d M y', $label)->startOfDay();
                                    if ($d <= $today) $todayIdx = $idx;
                                    else break;
                                }
                                $plannedToday = $pvData[$todayIdx] ?? 0;
                            @endphp
                            {{ $plannedToday }}%
                        </h4>
                    </div>
                </div>
                <div class="col-md-4">
                    @php
                        $variance = (end($avData) ?? 0) - $plannedToday;
                        $varColor = $variance >= 0 ? '#10b981' : '#ef4444';
                    @endphp
                    <div class="p-3 bg-white rounded-3 border">
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Deviasi (Variance)</p>
                        <h4 class="fw-black mb-0" style="color: {{ $varColor }};">
                            {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DETAIL TABLE CARD --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table text-primary me-2"></i>Time Schedule & Weight Distribution</h5>
            <p class="text-muted mb-0 small">Detail rencana mingguan berdasarkan bobot aktivitas</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-bordered mb-0 text-nowrap" style="font-size: 0.75rem; border-color: #e2e8f0;">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        <tr class="text-center align-middle">
                            <th rowspan="2" class="bg-light sticky-col" style="left: 0; min-width: 250px; z-index: 11;">ACTIVITY</th>
                            <th colspan="2">TANGGAL</th>
                            <th rowspan="2">DURASI</th>
                            <th rowspan="2">BOBOT</th>
                            @foreach($months as $month => $ws)
                                <th colspan="{{ count($ws) }}" class="text-uppercase">{{ $month }}</th>
                            @endforeach
                        </tr>
                        <tr class="text-center align-middle">
                            <th>MULAI</th>
                            <th>AKHIR</th>
                            @foreach($weeklyPeriods as $wp)
                                <th style="min-width: 60px;">{{ $wp['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $weeklyTotals = array_fill(0, count($weeklyPeriods), 0); @endphp
                        @foreach($tableHierarchy as $sub)
                            <tr class="bg-primary-subtle fw-bold">
                                <td class="sticky-col" style="left: 0; background: inherit;">{{ $sub['name'] }}</td>
                                <td colspan="2"></td>
                                <td></td>
                                <td class="text-center">{{ number_format($sub['weight'], 2) }}%</td>
                                @foreach($weeklyPeriods as $wp) <td></td> @endforeach
                            </tr>
                            @foreach($sub['milestones'] as $ms)
                                <tr class="bg-light fw-semibold">
                                    <td class="ps-4 sticky-col" style="left: 0; background: inherit;">{{ $ms['name'] }}</td>
                                    <td colspan="2"></td>
                                    <td></td>
                                    <td class="text-center">{{ number_format($ms['weight'], 2) }}%</td>
                                    @foreach($weeklyPeriods as $wp) <td></td> @endforeach
                                </tr>
                                @foreach($ms['activities'] as $act)
                                    <tr>
                                        <td class="ps-5 sticky-col" style="left: 0; background: #fff;">{{ $act['name'] }}</td>
                                        <td class="text-center">{{ $act['start']->format('d-M-y') }}</td>
                                        <td class="text-center">{{ $act['end']->format('d-M-y') }}</td>
                                        <td class="text-center">{{ $act['duration'] }} hr</td>
                                        <td class="text-center fw-medium">{{ number_format($act['weight'], 3) }}%</td>
                                        @foreach($act['period_values'] as $idx => $val)
                                            @php $weeklyTotals[$idx] += $val; @endphp
                                            <td class="text-center {{ $val > 0 ? 'bg-success-subtle text-success fw-bold' : 'text-muted opacity-25' }}" style="font-size: 0.65rem;">
                                                {{ $val > 0 ? number_format($val, 2) : '' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold sticky-bottom" style="z-index: 10;">
                        <tr>
                            <td class="text-end sticky-col bg-light" style="left: 0;">RENCANA MINGGUAN</td>
                            <td colspan="4" class="bg-light border-start-0"></td>
                            @foreach($weeklyTotals as $val)
                                <td class="text-center text-primary bg-light">{{ number_format($val, 2) }}%</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="text-end sticky-col bg-light" style="left: 0;">RENCANA KOMULATIF</td>
                            <td colspan="4" class="bg-light border-start-0"></td>
                            @php $cum = 0; @endphp
                            @foreach($weeklyTotals as $val)
                                @php $cum += $val; @endphp
                                <td class="text-center bg-primary text-white">{{ number_format(min(100, $cum), 2) }}%</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .sticky-col {
        position: sticky;
        border-right: 2px solid #e2e8f0 !important;
    }
    .table-responsive::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('scurveChart').getContext('2d');
        
        const pvGradient = ctx.createLinearGradient(0, 0, 0, 350);
        pvGradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
        pvGradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

        const avGradient = ctx.createLinearGradient(0, 0, 0, 350);
        avGradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        avGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        const labels = @json($labels);
        const pvData = @json($pvData);
        const avData = @json($avData);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Planned Value (PV)',
                        data: pvData,
                        borderColor: '#6366f1',
                        backgroundColor: pvGradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#6366f1',
                    },
                    {
                        label: 'Actual Value (AV)',
                        data: avData,
                        borderColor: '#10b981',
                        backgroundColor: avGradient,
                        borderWidth: 4,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#10b981',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y}%`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { autoSkip: true, maxTicksLimit: 10, color: '#94a3b8', font: { size: 10 } }
                    },
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: { stepSize: 20, color: '#94a3b8', font: { size: 10 }, callback: (v) => v + '%' }
                    }
                }
            }
        });
    })();
</script>
