<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WeeklyProjectExport;

class ProjectController extends Controller
{
    /**
     * Display the Main Dashboard view.
     */
    public function index()
    {
        $totalPrograms = \App\Models\Program::count();
        $totalActivities = \App\Models\Activity::count();
        
        $activities = \App\Models\Activity::with('milestone.subProgram.program')->get();
        
        $statusCounts = [
            'Draft' => 0,
            'To Do' => 0,
            'On Progress' => 0,
            'On Hold' => 0,
            'Done' => 0,
            'Cancelled' => 0,
        ];
        
        $systemStatusCounts = [
            'Upcoming' => 0,
            'Active' => 0,
            'Delayed' => 0,
            'Completed' => 0,
        ];
        
        foreach ($activities as $act) {
            if (array_key_exists($act->status, $statusCounts)) {
                $statusCounts[$act->status]++;
            }
            $sys = $act->system_status;
            if (array_key_exists($sys, $systemStatusCounts)) {
                $systemStatusCounts[$sys]++;
            }
        }
        
        // Let's get the 5 most recent or upcoming activities for quick view
        $recentActivities = \App\Models\Activity::with('milestone.subProgram.program')
                            ->orderBy('start_date', 'asc')
                            ->where('progress', '<', 100)
                            ->take(5)
                            ->get();

        return view('projects.index', compact(
            'totalPrograms', 
            'totalActivities', 
            'statusCounts', 
            'systemStatusCounts',
            'recentActivities'
        ));
    }

    /**
     * Display the Gantt Chart view.
     */
    public function gantt(Request $request)
    {
        $programs = \App\Models\Program::all();
        $selectedProgramId = $request->query('program_id') ?? ($programs->first() ? $programs->first()->id : null);
        
        return view('projects.gantt', compact('programs', 'selectedProgramId'));
    }

    /**
     * Display the Calendar view.
     */
    public function calendar(Request $request)
    {
        $programs = \App\Models\Program::all();
        $selectedProgramId = $request->query('program_id') ?? ($programs->first() ? $programs->first()->id : null);
        
        return view('projects.calendar', compact('programs', 'selectedProgramId'));
    }

    /**
     * Export the weekly timeline to Excel.
     */
    public function exportExcel(Request $request)
    {
        $programId = $request->query('program_id');
        $programs = \App\Models\Program::all();
        $selectedProgramId = $programId ?? ($programs->first() ? $programs->first()->id : null);
        
        return Excel::download(new WeeklyProjectExport($selectedProgramId), 'project_weekly_timeline.xlsx');
    }
}
