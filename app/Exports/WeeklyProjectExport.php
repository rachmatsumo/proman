<?php

namespace App\Exports;

use App\Models\Activity;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class WeeklyProjectExport implements FromView
{
    protected $programId;

    public function __construct($programId = null)
    {
        $this->programId = $programId;
    }

    public function view(): View
    {
        $query = Activity::orderBy('start_date', 'asc');
        if ($this->programId) {
            $query->whereHas('milestone.subProgram', function($q) {
                $q->where('program_id', $this->programId);
            });
        }
        $activities = $query->get();

        if ($activities->isEmpty()) {
            return view('exports.weekly_projects', [
                'activities' => [],
                'weeks' => [],
                'programs' => collect()
            ]);
        }

        $minDate = $activities->min('start_date');
        $maxDate = $activities->max('end_date');

        // Create an array of weeks from minDate to maxDate
        $weeks = [];
        $currentDate = $minDate->copy()->startOfWeek();
        $endDate = $maxDate->copy()->endOfWeek();
        
        $weekNumber = 1;
        while ($currentDate->lte($endDate)) {
            $weeks[] = [
                'label' => 'Minggu ke-' . $weekNumber,
                'start' => $currentDate->copy(),
                'end' => $currentDate->copy()->endOfWeek()
            ];
            $currentDate->addWeek();
            $weekNumber++;
        }

        $programsQuery = \App\Models\Program::with(['subPrograms.milestones.activities']);
        if ($this->programId) {
            $programsQuery->where('id', $this->programId);
        }
        $programs = $programsQuery->get();

        return view('exports.weekly_projects', [
            'activities' => $activities,
            'weeks' => $weeks,
            'programs' => $programs
        ]);
    }
}
