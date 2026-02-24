<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SubProgram;
use App\Models\Milestone;
use App\Models\Activity;
use App\Observers\HierarchyObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the observer for all hierarchy models
        SubProgram::observe(HierarchyObserver::class);
        Milestone::observe(HierarchyObserver::class);
        Activity::observe(HierarchyObserver::class);
    }
}
