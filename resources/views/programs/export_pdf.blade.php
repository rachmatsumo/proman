<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Program - {{ $program->name }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-bg {
            background-color: #312e81;
            color: white;
            padding: 10px;
            font-size: 11pt;
            font-weight: bold;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.main-table th, table.main-table td {
            border: 1px solid #e2e8f0;
            padding: 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.main-table th {
            white-space: nowrap;
        }
        table.main-table th.col-name {
            white-space: normal;
        }
        
        /* Column Widths */
        .col-no { width: 35px; text-align: center; }
        .col-name { width: 280px; text-align: left; }
        .col-bobot { width: 45px; text-align: center; font-size: 7.5pt; }
        .col-progress { width: 45px; text-align: center; font-size: 7.5pt; }
        .col-date { width: 62px; text-align: center; font-size: 7.5pt; white-space: nowrap; }
        .col-status { width: 55px; text-align: center; font-size: 7pt; white-space: nowrap; } /* Status or Count */
        .col-timeline { width: 10px; min-width: 10px; padding: 0 !important; }

        /* Headers */
        .month-header {
            background-color: #312e81;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
            height: 22px;
            border: 1px solid #1e1b4b !important;
        }
        .week-header {
            background-color: #e0e7ff;
            color: #1e1b4b;
            font-weight: bold;
            text-align: center;
            font-size: 6.5pt;
            padding: 2px 0 !important;
            height: 18px;
            border: 1px solid #c7d2fe !important;
        }

        /* Tiered Styling */
        .row-sub {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e1b4b;
            font-size: 8.5pt;
        }
        .row-milestone {
            background-color: #f5f3ff;
            font-weight: bold;
            color: #4338ca;
            font-size: 8pt;
        }
        .row-activity {
            background-color: #ffffff;
            font-size: 7.5pt;
        }
        .row-subactivity {
            background-color: #ffffff;
            color: #64748b;
            font-size: 7pt;
            font-style: italic;
        }

        /* Timeline Colors */
        .bar-sub { background-color: #312e81 !important; }
        .bar-ms { background-color: #818cf8 !important; }
        .bar-act { background-color: #3b82f6 !important; }
        .bar-subact { background-color: #94a3b8 !important; }

        /* Helpers */
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .page-break { page-break-after: always; }
        .staircase-1 { padding-left: 5px !important; }
        .staircase-2 { padding-left: 15px !important; }
        .staircase-3 { padding-left: 25px !important; }
        .staircase-4 { padding-left: 35px !important; }

    </style>
</head>
<body>

    @php
        $prefix = $program->prefix ?? '1';
        $programStart = $program->start_date?->copy()->startOfMonth();

        function getTimelineRange($start, $end, $programStart, $totalMonths) {
            if (!$start || !$end || !$programStart) return [];
            
            $getColIdx = function($date) use ($programStart) {
                $mDiff = ($date->year - $programStart->year) * 12 + ($date->month - $programStart->month);
                $day = (int)$date->format('j');
                $week = min(4, ceil($day / 7.75));
                return ($mDiff * 4) + ($week - 1);
            };

            $sIdx = $getColIdx($start);
            $eIdx = $getColIdx($end);
            
            $totalCols = $totalMonths * 4;
            $sIdx = max(0, $sIdx);
            $eIdx = min($totalCols - 1, $eIdx);

            $range = [];
            for ($i = $sIdx; $i <= $eIdx; $i++) {
                $range[] = $i;
            }
            return $range;
        }
    @endphp

    <!-- PAGE 1: RINGKASAN PROGRAM -->
    <div class="page-break">
        <table class="header-table">
            <tr>
                <td class="header-bg">RINGKASAN PROGRAM: {{ $program->name }}</td>
            </tr>
        </table>

        <div class="info-section">
            <div class="info-row"><span class="info-label">Tema:</span> {{ $program->theme }}</div>
            <div class="info-row"><span class="info-label">Periode:</span> {{ $program->start_date?->format('d/m/Y') ?? '-' }} s/d {{ $program->end_date?->format('d/m/Y') ?? '-' }}</div>
            <div class="info-row"><span class="info-label">Progress Total:</span> <strong>{{ $overallProgramProgress }}%</strong></div>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-no week-header">No</th>
                    <th rowspan="2" class="col-name week-header">Sub Program / Milestone</th>
                    <th rowspan="2" class="col-bobot week-header">Bobot (%)</th>
                    <th rowspan="2" class="col-progress week-header">Progress</th>
                    <th rowspan="2" class="col-date week-header">Mulai</th>
                    <th rowspan="2" class="col-date week-header">Selesai</th>
                    <th rowspan="2" class="col-status week-header">Jml Activity</th>
                    @foreach($timelineMeta as $m)
                        <th colspan="4" class="month-header">{{ $m['month'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($timelineMeta as $m)
                        @for($w=1; $w<=4; $w++)
                            <th class="week-header col-timeline">{{ $w }}</th>
                        @endfor
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($allSubProgValues as $idx => $item)
                    @php
                        $sub = $item['sub'];
                        $subNum = $prefix . '.' . ($idx + 1);
                        $subTimeline = getTimelineRange($sub->start_date, $sub->end_date, $programStart, count($timelineMeta));
                        $subActTotal = $sub->milestones->flatMap->activities->count();
                    @endphp
                    <tr class="row-sub">
                        <td class="text-center">{{ $subNum }}</td>
                        <td class="staircase-1">{{ $sub->name }}</td>
                        <td class="text-center">{{ $sub->bobot ?? '-' }}</td>
                        <td class="text-center">{{ $item['progress'] }}%</td>
                        <td class="text-center">{{ $sub->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-center">{{ $sub->end_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-center">{{ $subActTotal }}</td>
                        @for($i=0; $i < count($timelineMeta)*4; $i++)
                            <td class="col-timeline {{ in_array($i, $subTimeline) ? 'bar-sub' : '' }}"></td>
                        @endfor
                    </tr>

                    @php $mGroup = 1; $mCount = 0; @endphp
                    @foreach($sub->milestones as $ms)
                        @if($ms->type === 'divider' || $ms->type === 'key_result')
                            @if($ms->type === 'divider') @php $mGroup++; $mCount = 0; @endphp @endif
                            @continue
                        @endif

                        @php
                            $mCount++;
                            $msNum = 'M.' . $mGroup . '.' . $mCount;
                            $msProgress = $ms->activities->isEmpty() ? 0 : round($ms->activities->avg('progress'));
                            $msTimeline = getTimelineRange($ms->start_date, $ms->end_date, $programStart, count($timelineMeta));
                        @endphp
                        <tr class="row-milestone">
                            <td class="text-center" style="font-size: 7pt; color: #4338ca;">{{ $msNum }}</td>
                            <td class="staircase-2">{{ $ms->name }}</td>
                            <td class="text-center">{{ $ms->bobot ?? '-' }}</td>
                            <td class="text-center">{{ $msProgress }}%</td>
                            <td class="text-center">{{ $ms->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-center">{{ $ms->end_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-center">{{ $ms->activities->count() }}</td>
                            @for($i=0; $i < count($timelineMeta)*4; $i++)
                                <td class="col-timeline {{ in_array($i, $msTimeline) ? 'bar-ms' : '' }}"></td>
                            @endfor
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 10px; font-size: 7pt; color: #94a3b8; text-align: right;">
            Halaman 1 | Dicetak pada: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- DETAIL SUB-PROGRAMS (ONE PER PAGE) -->
    @foreach($allSubProgValues as $idx => $item)
        @php
            $sub = $item['sub'];
            $subIndex = $idx + 1;
            $sheetCode = $prefix . '.' . $subIndex;
        @endphp
        
        <div class="{{ $loop->last ? '' : 'page-break' }}">
            <table class="header-table">
                <tr>
                    <td class="header-bg">DETAIL SUB PROGRAM {{ $sheetCode }}: {{ $sub->name }}</td>
                </tr>
                <tr>
                    <td style="background-color: #4f46e5; color: white; padding: 5px 10px; font-weight: bold; font-size: 9pt;">
                        Progress Sub Program: {{ $item['progress'] }}%
                    </td>
                </tr>
            </table>

            <table class="main-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no week-header">No</th>
                        <th rowspan="2" class="col-name week-header">Milestone / Activity</th>
                        <th rowspan="2" class="col-bobot week-header">Bobot (%)</th>
                        <th rowspan="2" class="col-progress week-header">Progress</th>
                        <th rowspan="2" class="col-date week-header">Mulai</th>
                        <th rowspan="2" class="col-date week-header">Selesai</th>
                        <th rowspan="2" class="col-status week-header">Status</th>
                        @foreach($timelineMeta as $m)
                            <th colspan="4" class="month-header">{{ $m['month'] }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($timelineMeta as $m)
                            @for($w=1; $w<=4; $w++)
                                <th class="week-header col-timeline">{{ $w }}</th>
                            @endfor
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $mGroup = 1; $mCount = 0; @endphp
                    @foreach($sub->milestones as $ms)
                        @if($ms->type === 'divider')
                            @php $mGroup++; $mCount = 0; @endphp
                            @continue
                        @endif
                        @if($ms->type === 'key_result') @continue @endif

                        @php
                            $mCount++;
                            $msNum = 'M.' . $mGroup . '.' . $mCount;
                            $msProgress = $ms->activities->isEmpty() ? 0 : round($ms->activities->avg('progress'));
                            $msTimeline = getTimelineRange($ms->start_date, $ms->end_date, $programStart, count($timelineMeta));
                        @endphp
                        <tr class="row-milestone">
                            <td class="text-center" style="font-size: 7pt; color: #4338ca;">{{ $msNum }}</td>
                            <td class="staircase-1">{{ $ms->name }}</td>
                            <td class="text-center">{{ $ms->bobot ?? '-' }}</td>
                            <td class="text-center">{{ $msProgress }}%</td>
                            <td class="text-center">{{ $ms->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-center">{{ $ms->end_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-center">-</td>
                            @for($i=0; $i < count($timelineMeta)*4; $i++)
                                <td class="col-timeline {{ in_array($i, $msTimeline) ? 'bar-ms' : '' }}"></td>
                            @endfor
                        </tr>

                        @php $actCount = 0; @endphp
                        @foreach($ms->activities as $act)
                            @php
                                $actCount++;
                                $actNum = $mGroup . '.' . $mCount . '.' . $actCount;
                                $actTimeline = getTimelineRange($act->start_date, $act->end_date, $programStart, count($timelineMeta));
                            @endphp
                            <tr class="row-activity">
                                <td class="text-center" style="font-size: 7pt; color: #64748b;">{{ $actNum }}</td>
                                <td class="staircase-2">{{ $act->name }}</td>
                                <td class="text-center">-</td>
                                <td class="text-center">{{ $act->progress }}%</td>
                                <td class="text-center">{{ $act->start_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-center">{{ $act->end_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-center" style="font-size: 6pt;">{{ $act->status }}</td>
                                @for($i=0; $i < count($timelineMeta)*4; $i++)
                                    <td class="col-timeline {{ in_array($i, $actTimeline) ? 'bar-act' : '' }}"></td>
                                @endfor
                            </tr>

                            @foreach($act->subActivities as $subActIdx => $sa)
                                @php
                                    $saNum = $actNum . '.' . ($subActIdx + 1);
                                    $saTimeline = getTimelineRange($sa->start_date, $sa->end_date, $programStart, count($timelineMeta));
                                @endphp
                                <tr class="row-subactivity">
                                    <td class="text-center" style="font-size: 6.5pt; color: #94a3b8;">{{ $saNum }}</td>
                                    <td class="staircase-3">{{ $sa->name }}</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center">{{ $sa->progress }}%</td>
                                    <td class="text-center">{{ $sa->start_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-center">{{ $sa->end_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-center" style="font-size: 5.5pt;">{{ $sa->status }}</td>
                                    @for($i=0; $i < count($timelineMeta)*4; $i++)
                                        <td class="col-timeline {{ in_array($i, $saTimeline) ? 'bar-subact' : '' }}"></td>
                                    @endfor
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top: 10px; font-size: 7pt; color: #94a3b8; text-align: right;">
                Halaman {{ $loop->iteration + 1 }} | Dicetak pada: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    @endforeach

</body>
</html>
