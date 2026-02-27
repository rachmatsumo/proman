<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Http\Requests\StoreProgramRequest;
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
            'subPrograms.activityLogs',
            'subPrograms.milestones.attachments',
            'subPrograms.milestones.activityLogs',
            'subPrograms.milestones.activities.attachments',
            'subPrograms.milestones.activities.activityLogs',
        ])->findOrFail($id);

        $allUsers = \App\Models\User::all();

        $logs = collect();
        foreach ($program->subPrograms as $sub) {
            $logs = $logs->concat($sub->activityLogs);
            foreach ($sub->milestones as $ms) {
                $logs = $logs->concat($ms->activityLogs);
                foreach ($ms->activities as $act) {
                    $logs = $logs->concat($act->activityLogs);
                }
            }
        }
        $activityLogs = $logs->sortByDesc('created_at')->values();

        return view('programs.show', compact('program', 'activityLogs', 'allUsers'));
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

        foreach ($program->subPrograms as $sub) {
            $subMin = null;
            $subMax = null;
            $subTasks = [];

            foreach ($sub->milestones as $ms) {
                $msMin = null;
                $msMax = null;
                $msTasks = [];

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

                    $statusSlug = str_replace(' ', '-', $act->status ?? '');
                    $actId = 'act_' . $act->id;
                    $msTasks[] = [
                        'id'           => $actId,
                        'name'         => $act->name,
                        'start'        => $start?->format('Y-m-d'),
                        'end'          => $end?->format('Y-m-d'),
                        'progress'     => $act->progress ?? 0,
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
                            'progress'     => $subAct->progress ?? 0,
                            'custom_class' => 'bar-subactivity status-' . $subStatusSlug,
                            'dependencies' => $actId,
                        ];
                    }
                }

                // Milestone Rollup Header
                $ganttTasks[] = [
                    'id'           => 'ms_' . $ms->id,
                    'name'         => $ms->name,
                    'start'        => ($msMin ?? $ms->start_date)?->format('Y-m-d'),
                    'end'          => ($msMax ?? $ms->end_date)?->format('Y-m-d'),
                    'progress'     => 0,
                    'custom_class' => 'bar-milestone',
                    'dependencies' => 'sub_' . $sub->id,
                ];
                $ganttTasks = array_merge($ganttTasks, $msTasks);
            }

            // Sub Program Rollup Header
            $allGanttTasks[] = [
                'id'           => 'sub_' . $sub->id,
                'name'         => $sub->name,
                'start'        => ($subMin ?? $sub->start_date)?->format('Y-m-d'),
                'end'          => ($subMax ?? $sub->end_date)?->format('Y-m-d'),
                'progress'     => 0,
                'custom_class' => 'bar-subprogram',
                'dependencies' => 'prog_' . $program->id,
            ];
            $allGanttTasks = array_merge($allGanttTasks, $ganttTasks ?? []);
            $ganttTasks = []; // Reset for next sub
        }

        // Program Rollup Header
        $programData['start'] = ($progMin ?? $program->start_date)?->format('Y-m-d');
        $programData['end']   = ($progMax ?? $program->end_date)?->format('Y-m-d');
        
        array_unshift($allGanttTasks, $programData);
        $ganttTasks = $allGanttTasks;

        $ganttTasksJson = json_encode($ganttTasks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return view('programs.partials.gantt', compact('program', 'ganttTasksJson'));
    }

    public function partialCalendar(string $id)
    {
        $program = Program::findOrFail($id);
        return view('programs.partials.calendar', compact('program'));
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

        return redirect()->route('projects.gantt')->with('success', 'Program deleted successfully.');
    }
}
