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
        'name',
        'description',
        'bobot',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'bobot' => 'float',
    ];

    public function subProgram()
    {
        return $this->belongsTo(SubProgram::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
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
