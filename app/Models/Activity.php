<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Attachment;
use App\Models\ActivityLog;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'milestone_id',
        'name',
        'description',
        'bobot',
        'start_date',
        'end_date',
        'progress',
        'status', // Manual Status
        'uic',
        'pic',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'bobot' => 'float',
        'sort_order' => 'integer',
    ];

    protected $appends = ['system_status'];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * Get the computed system_status of the activity based on progress, dates, and current time.
     * System Status logic (Otomatis):
     * - progress == 100 -> Completed
     * - progress < 100 and end_date < today -> Delayed
     * - progress == 0 and start_date > today -> Upcoming
     * - (progress > 0 or start_date <= today) and progress < 100 -> Active
     * - At Risk (could be if end is soon and progress is low, I will omit standard At Risk unless strictly defined, but will map 'delayed' to At Risk loosely or stick to the defined list: Upcoming, Active, Delayed, Completed)
     */
    public function getSystemStatusAttribute()
    {
        $today = Carbon::now()->startOfDay();

        if ($this->progress == 100) {
            return 'Completed';
        }

        if ($this->end_date && $this->end_date->startOfDay()->lt($today)) {
            return 'Delayed';
        }

        if ($this->progress == 0 && $this->start_date && $this->start_date->startOfDay()->gt($today)) {
            return 'Upcoming';
        }

        return 'Active';
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function subActivities()
    {
        return $this->hasMany(SubActivity::class)->orderBy('sort_order');
    }
}
