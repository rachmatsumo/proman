<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agenda extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'location',
        'uic',
        'meeting_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * The PICs (Users) assigned to this agenda.
     */
    public function pics()
    {
        return $this->belongsToMany(User::class, 'agenda_user');
    }
}
