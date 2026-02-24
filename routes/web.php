<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\ProgramController;
use App\Http\Controllers\Web\SubProgramController;
use App\Http\Controllers\Web\MilestoneController;
use App\Http\Controllers\Web\ActivityController;
use App\Http\Controllers\Web\AttachmentController;

Route::get('/', function () {
    return redirect()->route('projects.gantt');
});

Route::prefix('projects')->name('projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    
    Route::get('/gantt', [ProjectController::class, 'gantt'])->name('gantt');
    Route::get('/calendar', [ProjectController::class, 'calendar'])->name('calendar');
    Route::get('/export', [ProjectController::class, 'exportExcel'])->name('export');
});

// Program Partials for AJAX Tabs
Route::get('programs/{program}/partial-gantt', [ProgramController::class, 'partialGantt'])->name('programs.partial-gantt');
Route::get('programs/{program}/partial-calendar', [ProgramController::class, 'partialCalendar'])->name('programs.partial-calendar');

// CRUD Modules
Route::resource('programs', ProgramController::class);
Route::resource('sub_programs', SubProgramController::class);
Route::resource('milestones', MilestoneController::class);
Route::resource('activities', ActivityController::class);

// Attachments (polymorphic)
Route::post('/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

