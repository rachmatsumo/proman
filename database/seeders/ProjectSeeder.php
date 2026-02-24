<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\SubProgram;
use App\Models\Milestone;
use App\Models\Activity;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::now();

        // 1. Create a Program
        $program = Program::create([
            'name' => 'Sistem Informasi Manajemen',
            'description' => 'Pengembangan Sistem Informasi Terpadu 2026',
            'start_date' => $today->copy()->subDays(10),
            'end_date' => $today->copy()->addDays(60),
        ]);

        // 1.1 Create Sub Program
        $subProgram1 = SubProgram::create([
            'program_id' => $program->id,
            'name' => 'Pengembangan Modul Project Management',
            'description' => 'Fase 1: Gantt & Calendar',
            'start_date' => $today->copy()->subDays(5),
            'end_date' => $today->copy()->addDays(40),
        ]);

        // A. Milestone 1
        $milestone1 = Milestone::create([
            'sub_program_id' => $subProgram1->id,
            'name' => 'Database & Backend',
            'description' => 'Rest API & Migrations',
            'start_date' => $today->copy()->subDays(5),
            'end_date' => $today->copy()->addDays(10),
        ]);

        // Activities for Milestone 1
        Activity::create([
            'milestone_id' => $milestone1->id,
            'name' => 'Desain ERD',
            'start_date' => $today->copy()->subDays(5),
            'end_date' => $today->copy()->subDays(2),
            'progress' => 100, // Status: done
        ]);

        Activity::create([
            'milestone_id' => $milestone1->id,
            'name' => 'Migration & Seeder',
            'start_date' => $today->copy()->subDays(1),
            'end_date' => $today->copy()->addDays(2),
            'progress' => 50, // Status: on progress
        ]);

        // B. Milestone 2
        $milestone2 = Milestone::create([
            'sub_program_id' => $subProgram1->id,
            'name' => 'Frontend & View',
            'description' => 'Gantt Chart & Fullcalendar',
            'start_date' => $today->copy()->addDays(3),
            'end_date' => $today->copy()->addDays(20),
        ]);

        // Activities for Milestone 2
        Activity::create([
            'milestone_id' => $milestone2->id,
            'name' => 'Integrasi FullCalendar',
            'start_date' => $today->copy()->addDays(3),
            'end_date' => $today->copy()->addDays(10),
            'progress' => 0, // Status: not started
        ]);

        Activity::create([
            'milestone_id' => $milestone2->id,
            'name' => 'Integrasi Frappe Gantt',
            'start_date' => $today->copy()->addDays(11),
            'end_date' => $today->copy()->addDays(20),
            'progress' => 0, // Status: not started
        ]);
        
        // Activity that is delayed
        Activity::create([
            'milestone_id' => $milestone1->id,
            'name' => 'Setup Server (Delayed)',
            'start_date' => $today->copy()->subDays(10),
            'end_date' => $today->copy()->subDays(6),
            'progress' => 20, // Status: delayed (because end_date < today and progress < 100)
        ]);
    }
}
