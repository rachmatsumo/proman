<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'user_id',
        'action',
        'description',
        'old_data',
        'new_data',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    /**
     * Polymorphic: belongs to SubProgram, Milestone, or Activity
     */
    public function loggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Action badge color for UI
     */
    public function getActionColorAttribute(): array
    {
        return match ($this->action) {
            'created' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-plus-circle'],
            'updated' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'icon' => 'fa-pen-to-square'],
            'deleted' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'icon' => 'fa-trash'],
            default   => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-circle'],
        };
    }

    /**
     * Entity type label for UI
     */
    public function getEntityLabelAttribute(): string
    {
        return match ($this->loggable_type) {
            'App\\Models\\SubProgram' => 'Sub Program',
            'App\\Models\\Milestone'  => 'Milestone',
            'App\\Models\\Activity'   => 'Activity',
            default                  => class_basename($this->loggable_type),
        };
    }

    /**
     * Changed fields diff for updated actions
     */
    public function getChangedFieldsAttribute(): array
    {
        if ($this->action !== 'updated' || !$this->old_data || !$this->new_data) {
            return [];
        }
        $changed = [];
        foreach ($this->new_data as $field => $newVal) {
            $oldVal = $this->old_data[$field] ?? null;
            if ($oldVal !== $newVal) {
                $changed[] = [
                    'field' => $field,
                    'old'   => $oldVal,
                    'new'   => $newVal,
                ];
            }
        }
        return $changed;
    }
}
