<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Http\Requests\StoreProgramRequest;
use App\Exports\ProgramExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::latest()->get();
        return view('programs.index', compact('programs'));
    }

    public function create()
    {
        return view('programs.create');
    }

    public function store(StoreProgramRequest $request)
    {
        Program::create($request->validated());
        return redirect()->route('programs.index')->with('success', 'Program created successfully.');
    }

    public function show(string $id)
    {
        $program = Program::with([
            'members',
            'subPrograms.attachments',
            'subPrograms.milestones.attachments',
            'subPrograms.milestones.activities.attachments',
        ])->findOrFail($id);

        $allUsers = \App\Models\User::all();

        // Initial load of history (first 20)
        $subIds = $program->subPrograms->pluck('id')->toArray();
        $msIds  = $program->subPrograms->flatMap->milestones->pluck('id')->toArray();
        $actIds = $program->subPrograms->flatMap->milestones->flatMap->activities->pluck('id')->toArray();

        $activityLogs = \App\Models\ActivityLog::with('user')
            ->where(function($q) use ($subIds, $msIds, $actIds) {
                $q->where(fn($sq) => $sq->where('loggable_type', 'App\Models\SubProgram')->whereIn('loggable_id', $subIds))
                  ->orWhere(fn($sq) => $sq->where('loggable_type', 'App\Models\Milestone')->whereIn('loggable_id', $msIds))
                  ->orWhere(fn($sq) => $sq->where('loggable_type', 'App\Models\Activity')->whereIn('loggable_id', $actIds));
            })
            ->latest()
            ->paginate(20);

        return view('programs.show', compact('program', 'activityLogs', 'allUsers'));
    }

    public function history(Request $request, string $id)
    {
        $program = Program::with(['subPrograms.milestones.activities'])->findOrFail($id);
        
        $subIds = $program->subPrograms->pluck('id')->toArray();
        $msIds  = $program->subPrograms->flatMap->milestones->pluck('id')->toArray();
        $actIds = $program->subPrograms->flatMap->milestones->flatMap->activities->pluck('id')->toArray();

        $query = \App\Models\ActivityLog::with('user')
            ->where(function($q) use ($subIds, $msIds, $actIds) {
                $q->where(fn($sq) => $sq->where('loggable_type', 'App\Models\SubProgram')->whereIn('loggable_id', $subIds))
                  ->orWhere(fn($sq) => $sq->where('loggable_type', 'App\Models\Milestone')->whereIn('loggable_id', $msIds))
                  ->orWhere(fn($sq) => $sq->where('loggable_type', 'App\Models\Activity')->whereIn('loggable_id', $actIds));
            });

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('description', 'like', "%$s%")
                  ->orWhere('action', 'like', "%$s%");
            });
        }

        $activityLogs = $query->latest()
            ->take(500)
            ->paginate(20);

        if ($request->ajax()) {
            return view('programs.partials.history_list', compact('activityLogs'))->render();
        }

        return redirect()->route('programs.show', $id);
    }

    public function attachments(Request $request, string $id)
    {
        $program = Program::with(['subPrograms.milestones.activities'])->findOrFail($id);
        
        $subIds = $program->subPrograms->pluck('id')->toArray();
        $msIds  = $program->subPrograms->flatMap->milestones->pluck('id')->toArray();
        $actIds = $program->subPrograms->flatMap->milestones->flatMap->activities->pluck('id')->toArray();
        $subActIds = $program->subPrograms->flatMap->milestones->flatMap->activities->flatMap->subActivities->pluck('id')->toArray();

        $query = \App\Models\Attachment::where(function($q) use ($subIds, $msIds, $actIds, $subActIds) {
                $q->where(fn($sq) => $sq->where('attachable_type', 'App\Models\SubProgram')->whereIn('attachable_id', $subIds))
                  ->orWhere(fn($sq) => $sq->where('attachable_type', 'App\Models\Milestone')->whereIn('attachable_id', $msIds))
                  ->orWhere(fn($sq) => $sq->where('attachable_type', 'App\Models\Activity')->whereIn('attachable_id', $actIds))
                  ->orWhere(fn($sq) => $sq->where('attachable_type', 'App\Models\SubActivity')->whereIn('attachable_id', $subActIds));
            });

        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('original_filename', 'like', "%$s%");
            });
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->type) {
            if ($request->type === 'file') {
                $query->whereNotNull('file_path');
            } elseif ($request->type === 'note') {
                $query->whereNull('file_path');
            } else {
                $query->where('type', $request->type);
            }
        }

        $attachments = $query->latest()->paginate(12);

        if ($request->ajax()) {
            return view('programs.partials.attachments_list', compact('attachments'))->render();
        }

        return redirect()->route('programs.show', $id);
    }

    public function edit(string $id)
    {
        $program = Program::findOrFail($id);
        return view('programs.edit', compact('program'));
    }

    public function partialGantt(string $id)
    {
        $program = Program::with(['subPrograms.milestones.activities.subActivities'])->findOrFail($id);

        $ganttTasks = [];

        $programData = [
            'id'           => 'prog_' . $program->id,
            'name'         => $program->name,
            'start'        => $program->start_date?->format('Y-m-d'),
            'end'          => $program->end_date?->format('Y-m-d'),
            'progress'     => 0,
            'custom_class' => 'bar-program',
            'dependencies' => null,
        ];

        $allGanttTasks = [];
        $progMin = null;
        $progMax = null;
        $allSubProgress = [];

        foreach ($program->subPrograms as $sub) {
            $subMin = null;
            $subMax = null;
            $subTasks = [];
            $allMsProgress = [];

            foreach ($sub->milestones as $ms) {
                // Skip section dividers — they have no timeline
                if ($ms->type === 'divider') continue;

                $msMin = null;
                $msMax = null;
                $msTasks = [];
                $msActProgress = [];

                foreach ($ms->activities as $act) {
                    $start = $act->start_date;
                    $end   = $act->end_date;

                    if ($start) {
                        if (!$msMin || $start < $msMin) $msMin = $start;
                        if (!$subMin || $start < $subMin) $subMin = $start;
                        if (!$progMin || $start < $progMin) $progMin = $start;
                    }
                    if ($end) {
                        if (!$msMax || $end > $msMax) $msMax = $end;
                        if (!$subMax || $end > $subMax) $subMax = $end;
                        if (!$progMax || $end > $progMax) $progMax = $end;
                    }

                    $actProgress = (int)($act->progress ?? 0);
                    $msActProgress[] = $actProgress;

                    $statusSlug = str_replace(' ', '-', $act->status ?? '');
                    $actId = 'act_' . $act->id;
                    $msTasks[] = [
                        'id'           => $actId,
                        'name'         => $act->name,
                        'start'        => $start?->format('Y-m-d'),
                        'end'          => $end?->format('Y-m-d'),
                        'progress'     => $actProgress,
                        'custom_class' => 'bar-activity status-' . $statusSlug,
                        'dependencies' => 'ms_' . $ms->id,
                    ];

                    // Sub Activities
                    foreach ($act->subActivities as $subAct) {
                        $subStart = $subAct->start_date;
                        $subEnd   = $subAct->end_date;

                        if ($subStart) {
                            if (!$msMin || $subStart < $msMin) $msMin = $subStart;
                            if (!$subMin || $subStart < $subMin) $subMin = $subStart;
                            if (!$progMin || $subStart < $progMin) $progMin = $subStart;
                        }
                        if ($subEnd) {
                            if (!$msMax || $subEnd > $msMax) $msMax = $subEnd;
                            if (!$subMax || $subEnd > $subMax) $subMax = $subEnd;
                            if (!$progMax || $subEnd > $progMax) $progMax = $subEnd;
                        }

                        $subStatusSlug = str_replace(' ', '-', $subAct->status ?? '');
                        $msTasks[] = [
                            'id'           => 'subact_' . $subAct->id,
                            'name'         => $subAct->name,
                            'start'        => $subStart?->format('Y-m-d'),
                            'end'          => $subEnd?->format('Y-m-d'),
                            'progress'     => (int)($subAct->progress ?? 0),
                            'custom_class' => 'bar-subactivity status-' . $subStatusSlug,
                            'dependencies' => $actId,
                        ];
                    }
                }

                // Milestone Rollup header progress (avg of activities)
                $msFinalProgress = count($msActProgress) > 0 ? (int)round(array_sum($msActProgress) / count($msActProgress)) : 0;
                $allMsProgress[] = ['progress' => $msFinalProgress, 'bobot' => $ms->bobot];

                $ganttTasks[] = [
                    'id'           => 'ms_' . $ms->id,
                    'name'         => $ms->name,
                    'start'        => ($msMin ?? $ms->start_date)?->format('Y-m-d'),
                    'end'          => ($msMax ?? $ms->end_date)?->format('Y-m-d'),
                    'progress'     => $msFinalProgress,
                    'custom_class' => 'bar-milestone',
                    'dependencies' => 'sub_' . $sub->id,
                ];
                $ganttTasks = array_merge($ganttTasks, $msTasks);
            }

            // Sub Program Rollup progress
            $subFinalProgress = 0;
            if (count($allMsProgress) > 0) {
                $allHaveBobot = collect($allMsProgress)->every(fn($p) => $p['bobot'] !== null);
                if ($allHaveBobot) {
                    $weighted = 0;
                    foreach ($allMsProgress as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                    $subFinalProgress = (int)round(min(100, $weighted));
                } else {
                    $subFinalProgress = (int)round(collect($allMsProgress)->avg('progress'));
                }
            }
            $allSubProgress[] = ['progress' => $subFinalProgress, 'bobot' => $sub->bobot];

            $allGanttTasks[] = [
                'id'           => 'sub_' . $sub->id,
                'name'         => $sub->name,
                'start'        => ($subMin ?? $sub->start_date)?->format('Y-m-d'),
                'end'          => ($subMax ?? $sub->end_date)?->format('Y-m-d'),
                'progress'     => $subFinalProgress,
                'custom_class' => 'bar-subprogram',
                'dependencies' => 'prog_' . $program->id,
            ];
            $allGanttTasks = array_merge($allGanttTasks, $ganttTasks ?? []);
            $ganttTasks = []; // Reset for next sub
        }

        // Program Rollup header progress
        $overallProgress = 0;
        if (count($allSubProgress) > 0) {
            $allSubsHaveBobot = collect($allSubProgress)->every(fn($p) => $p['bobot'] !== null);
            if ($allSubsHaveBobot) {
                $weighted = 0;
                foreach ($allSubProgress as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                $overallProgress = (int)round(min(100, $weighted));
            } else {
                $overallProgress = (int)round(collect($allSubProgress)->avg('progress'));
            }
        }
        $programData['progress'] = $overallProgress;
        $programData['start']    = ($progMin ?? $program->start_date)?->format('Y-m-d');
        $programData['end']      = ($progMax ?? $program->end_date)?->format('Y-m-d');
        
        array_unshift($allGanttTasks, $programData);
        $ganttTasks = $allGanttTasks;

        $ganttTasksJson = json_encode($ganttTasks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return view('programs.partials.gantt', compact('program', 'ganttTasksJson'));
    }

    public function partialSCurve(string $id)
    {
        $program = Program::with(['subPrograms.milestones.activities.subActivities'])->findOrFail($id);
        
        // 1. Determine Project Range
        $start = $program->start_date;
        $end = $program->end_date;

        // Collect all activities to find dates if program dates are missing
        $allActivities = $program->subPrograms->flatMap->milestones->flatMap->activities;
        
        if (!$start || !$end) {
            foreach($allActivities as $act) {
                if ($act->start_date && (!$start || $act->start_date < $start)) $start = $act->start_date;
                if ($act->end_date && (!$end || $act->end_date > $end)) $end = $act->end_date;
            }
        }
        
        if (!$start) $start = now()->startOfMonth();
        if (!$end) $end = now()->endOfMonth();
        if ($end < $start) $end = $start->copy()->addMonth();

        // 2. Calculate Global Weights for each Activity
        $activityWeights = [];
        $subs = $program->subPrograms;
        $subCount = $subs->count();
        $allSubsHaveBobot = $subs->isNotEmpty() && $subs->every(fn($s) => $s->bobot !== null);
        
        foreach ($subs as $sub) {
            $subW = $allSubsHaveBobot ? ($sub->bobot / 100) : (1 / max(1, $subCount));
            
            $milestones = $sub->milestones->where('type', '!=', 'divider');
            $msCount = $milestones->count();
            $allMsHaveBobot = $milestones->isNotEmpty() && $milestones->every(fn($m) => $m->bobot !== null);
            
            foreach ($milestones as $ms) {
                $msW = $allMsHaveBobot ? ($ms->bobot / 100) : (1 / max(1, $msCount));
                
                $acts = $ms->activities;
                $actCount = $acts->count();
                // Activities don't usually have individual weights in this UI, they are averaged.
                // So ActWeight = 1 / actCount
                
                foreach ($acts as $act) {
                    $globalW = $subW * $msW * (1 / max(1, $actCount));
                    $activityWeights[$act->id] = $globalW;
                }
            }
        }

        // 3. Prepare Time-series Labels (Weekly for Table)
        $labels = [];
        $current = $start->copy()->startOfDay();
        $limit = $end->copy()->endOfDay();
        
        $daysDiff = $start->diffInDays($end);
        $step = 1;
        if ($daysDiff > 100) $step = 7; // Weekly points for chart if long
        
        $chartDates = [];
        while ($current <= $limit) {
            $labels[] = $current->format('d M y');
            $chartDates[] = $current->copy();
            $current->addDays($step);
        }
        if (end($chartDates)->format('Y-m-d') !== $limit->format('Y-m-d')) {
            $labels[] = $limit->format('d M y');
            $chartDates[] = $limit->copy();
        }

        // 3.1 Prepare Weekly Periods for Table
        $weeklyPeriods = [];
        $curr = $start->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        while ($curr <= $limit) {
            $wStart = $curr->copy();
            $wEnd = $curr->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
            
            // Limit to actual project range
            $actualStart = $wStart->lt($start) ? $start : $wStart;
            $actualEnd = $wEnd->gt($limit) ? $limit : $wEnd;
            
            if ($actualStart <= $actualEnd) {
                $weeklyPeriods[] = [
                    'start' => $actualStart->copy(),
                    'end' => $actualEnd->copy(),
                    'label' => 'W' . $curr->weekOfMonth,
                    'month_label' => $actualStart->translatedFormat('F Y'),
                ];
            }
            $curr->addWeek();
        }

        // 4. Calculate Planned Value (PV) & Table Data
        $pvData = [];
        foreach ($chartDates as $date) {
            $totalPV = 0;
            foreach ($allActivities as $act) {
                if (!$act->start_date || !$act->end_date) continue;
                $weight = $activityWeights[$act->id] ?? 0;
                $p = 0;
                if ($date >= $act->end_date) $p = 100;
                elseif ($date >= $act->start_date) {
                    $totalD = max(1, $act->start_date->diffInDays($act->end_date));
                    $elapsed = $act->start_date->diffInDays($date);
                    $p = ($elapsed / $totalD) * 100;
                }
                $totalPV += ($p * $weight);
            }
            $pvData[] = round($totalPV, 2);
        }

        // Calculate periodical weight distribution for each activity
        $tableHierarchy = [];
        foreach ($subs as $sub) {
            $subData = [
                'name' => $sub->name,
                'weight' => $allSubsHaveBobot ? $sub->bobot : (1 / max(1, $subCount)) * 100,
                'milestones' => []
            ];

            $milestones = $sub->milestones->where('type', '!=', 'divider');
            $msCount = $milestones->count();
            $allMsHaveBobot = $milestones->isNotEmpty() && $milestones->every(fn($m) => $m->bobot !== null);

            foreach ($milestones as $ms) {
                $msW = $allMsHaveBobot ? ($ms->bobot / 100) : (1 / max(1, $msCount));
                $msData = [
                    'name' => $ms->name,
                    'weight' => $msW * 100,
                    'activities' => []
                ];

                $acts = $ms->activities;
                $actCount = $acts->count();
                foreach ($acts as $act) {
                    $globalW = $activityWeights[$act->id] ?? 0;
                    $actPeriods = [];
                    foreach ($weeklyPeriods as $wp) {
                        // Days of this activity in this week
                        $overlapStart = $act->start_date->gt($wp['start']) ? $act->start_date : $wp['start'];
                        $overlapEnd = $act->end_date->lt($wp['end']) ? $act->end_date : $wp['end'];
                        
                        $val = 0;
                        if ($overlapStart <= $overlapEnd) {
                            $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
                            $totalActDays = $act->start_date->diffInDays($act->end_date) + 1;
                            $val = ($overlapDays / $totalActDays) * ($globalW * 100);
                        }
                        $actPeriods[] = round($val, 3);
                    }

                    $msData['activities'][] = [
                        'name' => $act->name,
                        'start' => $act->start_date,
                        'end' => $act->end_date,
                        'duration' => $act->start_date->diffInDays($act->end_date) + 1,
                        'weight' => $globalW * 100,
                        'period_values' => $actPeriods
                    ];
                }
                $subData['milestones'][] = $msData;
            }
            $tableHierarchy[] = $subData;
        }

        // 5. Calculate Actual Value (AV) - Using Logs
        $actIds = $allActivities->pluck('id')->toArray();
        $logs = \App\Models\ActivityLog::where('loggable_type', 'App\Models\Activity')
            ->whereIn('loggable_id', $actIds)
            ->where('action', 'updated')
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('loggable_id');

        $avData = [];
        $today = now();
        foreach ($chartDates as $date) {
            if ($date > $today && $date->diffInDays($today) > 0) break;
            $totalAV = 0;
            foreach ($allActivities as $act) {
                $weight = $activityWeights[$act->id] ?? 0;
                $actLogs = $logs->get($act->id);
                $progAt = 0;
                if ($actLogs) {
                    $l = $actLogs->filter(fn($l) => $l->created_at <= $date->copy()->endOfDay())->last();
                    if ($l) $progAt = $l->new_data['progress'] ?? 0;
                } elseif ($date->isToday()) $progAt = $act->progress;
                $totalAV += ($progAt * $weight);
            }
            $avData[] = round($totalAV, 2);
        }

        return view('programs.partials.s_curve', compact('program', 'labels', 'pvData', 'avData', 'tableHierarchy', 'weeklyPeriods'));
    }

    public function partialCalendar(string $id)
    {
        $program = Program::findOrFail($id);
        return view('programs.partials.calendar', compact('program'));
    }

    public function exportExcel(string $id)
    {
        $program  = Program::with([
            'subPrograms.milestones.activities.subActivities',
        ])->findOrFail($id);

        $filename = 'Program_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $program->name) . '_' . now()->format('YmdHis') . '.xlsx';

        return Excel::download(new ProgramExport($program), $filename);
    }

    public function exportPdf(string $id)
    {
        $program = Program::with([
            'subPrograms.milestones.activities.subActivities',
        ])->findOrFail($id);

        // Timeline metadata for the PDF (similar to Excel)
        $start = $program->start_date?->copy()->startOfMonth() ?? now()->startOfYear();
        $end   = $program->end_date?->copy()->endOfMonth() ?? now()->endOfYear();
        
        $timelineMeta = [];
        $current = $start->copy();
        while ($current <= $end) {
            $timelineMeta[] = [
                'month'     => $current->translatedFormat('F'),
                'year'      => $current->year,
                'month_val' => $current->month,
            ];
            $current->addMonth();
        }

        // Calculate Rollup Progress for Summary
        $allSubProgValues = [];
        foreach ($program->subPrograms as $sub) {
            $msProgValues = [];
            foreach ($sub->milestones as $ms) {
                if ($ms->type === 'divider') continue;
                $acts = $ms->activities;
                $msAvg = $acts->isEmpty() ? 0 : $acts->avg('progress');
                $msProgValues[] = ['progress' => $msAvg, 'bobot' => $ms->bobot];
            }
            
            $subProgress = 0;
            if (count($msProgValues) > 0) {
                $allHaveBobot = collect($msProgValues)->every(fn($p) => $p['bobot'] !== null);
                if ($allHaveBobot) {
                    $weighted = 0;
                    foreach ($msProgValues as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                    $subProgress = round($weighted);
                } else {
                    $subProgress = round(collect($msProgValues)->avg('progress'));
                }
            }
            $allSubProgValues[] = ['progress' => $subProgress, 'bobot' => $sub->bobot, 'sub' => $sub];
        }

        $overallProgramProgress = 0;
        if (count($allSubProgValues) > 0) {
            $allSubsHaveBobot = collect($allSubProgValues)->every(fn($p) => $p['bobot'] !== null);
            if ($allSubsHaveBobot) {
                $weighted = 0;
                foreach ($allSubProgValues as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                $overallProgramProgress = round($weighted);
            } else {
                $overallProgramProgress = round(collect($allSubProgValues)->avg('progress'));
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('programs.export_pdf', compact(
            'program', 
            'timelineMeta', 
            'allSubProgValues', 
            'overallProgramProgress'
        ));

        // Set paper to F4/Legal (approx 8.5 x 13/14 in) in landscape
        $pdf->setPaper('legal', 'landscape');

        $filename = 'Program_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $program->name) . '_' . now()->format('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    public function update(StoreProgramRequest $request, string $id)
    {
        $program = Program::findOrFail($id);
        $program->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Program updated successfully.',
                'data' => $program
            ]);
        }

        return redirect()->back()->with('success', 'Program updated successfully.');
    }

    public function destroy(string $id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Program deleted successfully.'
            ]);
        }

        return redirect()->route('programs.index')->with('success', 'Program deleted successfully.');
    }
}
