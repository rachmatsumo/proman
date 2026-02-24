<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\SubProgram;
use App\Models\Milestone;
use App\Models\Activity;

class ProjectController extends Controller
{
    /**
     * Get Hierarchical data for Gantt Chart
     */
    public function getGanttData(Request $request)
    {
        $programId = $request->query('program_id');
        $query = Program::with(['subPrograms.milestones.activities']);
        
        if ($programId) {
            $query->where('id', $programId);
        }
        
        $programs = $query->get();
        
        $ganttTasks = [];
        
        foreach ($programs as $program) {
            $ganttTasks[] = [
                'id' => 'prog_' . $program->id,
                'name' => $program->name,
                'start' => $program->start_date ? $program->start_date->format('Y-m-d') : null,
                'end' => $program->end_date ? $program->end_date->format('Y-m-d') : null,
                'progress' => 0, // Could be aggregated
                'custom_class' => 'bar-program',
            ];
            
            foreach ($program->subPrograms as $sub) {
                $ganttTasks[] = [
                    'id' => 'sub_' . $sub->id,
                    'name' => $sub->name,
                    'start' => $sub->start_date ? $sub->start_date->format('Y-m-d') : null,
                    'end' => $sub->end_date ? $sub->end_date->format('Y-m-d') : null,
                    'progress' => 0,
                    'dependencies' => 'prog_' . $program->id,
                    'custom_class' => 'bar-subprogram',
                ];
                
                foreach ($sub->milestones as $ms) {
                    $ganttTasks[] = [
                        'id' => 'ms_' . $ms->id,
                        'name' => $ms->name,
                        'start' => $ms->start_date ? $ms->start_date->format('Y-m-d') : null,
                        'end' => $ms->end_date ? $ms->end_date->format('Y-m-d') : null,
                        'progress' => 0,
                        'dependencies' => 'sub_' . $sub->id,
                        'custom_class' => 'bar-milestone',
                    ];
                    
                    foreach ($ms->activities as $act) {
                        $ganttTasks[] = [
                            'id' => 'act_' . $act->id,
                            'name' => $act->name,
                            'start' => $act->start_date->format('Y-m-d'),
                            'end' => $act->end_date->format('Y-m-d'),
                            'progress' => $act->progress,
                            'dependencies' => 'ms_' . $ms->id,
                            'custom_class' => 'bar-activity status-' . str_replace(' ', '-', $act->status),
                        ];
                    }
                }
            }
        }
        
        return response()->json($ganttTasks);
    }

    /**
     * Get flat activity data for FullCalendar
     */
    public function getCalendarData(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');
        $programId = $request->query('program_id');
        
        $query = Activity::query();
        
        if ($programId) {
            $query->whereHas('milestone.subProgram', function($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }
        
        if ($start && $end) {
            $query->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            });
        }
        
        $activities = $query->get();
        
        $events = $activities->map(function ($activity) {
            $color = '#3788d8'; // default blue (not started)
            
            if ($activity->status == 'done') {
                $color = '#28a745'; // green
            } elseif ($activity->status == 'delayed') {
                $color = '#dc3545'; // red
            } elseif ($activity->status == 'on progress') {
                $color = '#ffc107'; // yellow
            }

            return [
                'id' => $activity->id,
                'title' => $activity->name,
                'start' => $activity->start_date->format('Y-m-d'),
                'end' => $activity->end_date->addDay()->format('Y-m-d'), // FullCalendar exclusive end date
                'color' => $color,
                'extendedProps' => [
                    'progress' => $activity->progress,
                    'status' => $activity->status
                ]
            ];
        });
        
        return response()->json($events);
    }

    /**
     * Update task date from Gantt Chart
     */
    public function updateGanttTask(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
        ]);

        $idString = $request->id;
        $start = $request->start;
        $end = $request->end;

        if (strpos($idString, 'prog_') === 0) {
            $id = str_replace('prog_', '', $idString);
            Program::where('id', $id)->update(['start_date' => $start, 'end_date' => $end]);
        } elseif (strpos($idString, 'sub_') === 0) {
            $id = str_replace('sub_', '', $idString);
            SubProgram::where('id', $id)->update(['start_date' => $start, 'end_date' => $end]);
        } elseif (strpos($idString, 'ms_') === 0) {
            $id = str_replace('ms_', '', $idString);
            Milestone::where('id', $id)->update(['start_date' => $start, 'end_date' => $end]);
        } elseif (strpos($idString, 'act_') === 0) {
            $id = str_replace('act_', '', $idString);
            Activity::where('id', $id)->update(['start_date' => $start, 'end_date' => $end]);
        }

        return response()->json(['success' => true]);
    }
}
