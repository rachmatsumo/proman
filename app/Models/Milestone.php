<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Attachment;
use App\Models\ActivityLog;

class Milestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_program_id',
        'type',
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

    public function subProgram()
    {
        return $this->belongsTo(SubProgram::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('sort_order', 'asc');
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
