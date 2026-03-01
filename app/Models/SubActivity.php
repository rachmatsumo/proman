<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activity_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'progress',
        'status',
        'uic',
        'pic',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'sort_order' => 'integer',
    ];


    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getSystemStatusAttribute()
    {
        $today = date('Y-m-d');
        if ($this->progress >= 100) return 'Completed';
        if ($this->end_date < $today) return 'Delayed';
        if ($this->start_date > $today) return 'Upcoming';
        return 'Active';
    }

    protected static function booted()
    {
        static::saved(function ($subActivity) {
            if ($subActivity->activity) {
                $subActivity->activity->syncProgressFromSubActivities();
            }
        });

        static::deleted(function ($subActivity) {
            if ($subActivity->activity) {
                $subActivity->activity->syncProgressFromSubActivities();
            }
        });
    }
}
