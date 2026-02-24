<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Attachment;
use App\Models\ActivityLog;

class SubProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'bobot',
        'start_date',
        'end_date',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'bobot' => 'float',
        'sort_order' => 'integer',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order', 'asc');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }
}
