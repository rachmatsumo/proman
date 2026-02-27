<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prefix',
        'theme',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function subPrograms()
    {
        return $this->hasMany(SubProgram::class)->orderBy('sort_order', 'asc');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'program_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }
}
