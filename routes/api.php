<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('projects')->group(function () {
    Route::get('/gantt', [ProjectController::class, 'getGanttData']);
    Route::post('/gantt/update', [ProjectController::class, 'updateGanttTask']);
    Route::get('/calendar', [ProjectController::class, 'getCalendarData']);
});
