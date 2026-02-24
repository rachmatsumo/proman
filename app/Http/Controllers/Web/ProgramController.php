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
        return redirect()->route('projects.gantt')->with('success', 'Program created successfully.');
    }

    public function show(string $id)
    {
        $program = Program::with([
            'subPrograms.attachments',
            'subPrograms.activityLogs',
            'subPrograms.milestones.attachments',
            'subPrograms.milestones.activityLogs',
            'subPrograms.milestones.activities.attachments',
            'subPrograms.milestones.activities.activityLogs',
        ])->findOrFail($id);

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

        return view('programs.show', compact('program', 'activityLogs'));
    }

    public function edit(string $id)
    {
        $program = Program::findOrFail($id);
        return view('programs.edit', compact('program'));
    }

    public function partialGantt(string $id)
    {
        $program = Program::findOrFail($id);
        return view('programs.partials.gantt', compact('program'));
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
        return redirect()->route('projects.gantt')->with('success', 'Program updated successfully.');
    }

    public function destroy(string $id)
    {
        $program = Program::findOrFail($id);
        $program->delete();
        return redirect()->route('projects.gantt')->with('success', 'Program deleted successfully.');
    }
}
