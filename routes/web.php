<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\ProgramController;
use App\Http\Controllers\Web\SubProgramController;
use App\Http\Controllers\Web\MilestoneController;
use App\Http\Controllers\Web\ActivityController;
use App\Http\Controllers\Web\AttachmentController;
use App\Http\Controllers\Web\AgendaController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// Auth Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('projects.index');
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

    // Hierarchy Management
    Route::post('/hierarchy/update-order', [App\Http\Controllers\HierarchyOrderController::class, 'updateOrder'])->name('hierarchy.update-order');
    Route::post('/hierarchy/duplicate', [App\Http\Controllers\HierarchyDuplicateController::class, 'duplicate'])->name('hierarchy.duplicate');

    // User Management
    Route::resource('users', App\Http\Controllers\UserController::class);

    // Agenda Module
    Route::resource('agendas', AgendaController::class);

    // Program Member Management
    Route::post('programs/{program}/members', [App\Http\Controllers\Web\ProgramMemberController::class, 'store'])->name('programs.members.store');
    Route::put('programs/{program}/members/{user}', [App\Http\Controllers\Web\ProgramMemberController::class, 'update'])->name('programs.members.update');
    Route::delete('programs/{program}/members/{user}', [App\Http\Controllers\Web\ProgramMemberController::class, 'destroy'])->name('programs.members.destroy');
});

