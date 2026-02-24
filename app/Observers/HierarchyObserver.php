<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Single observer class shared by SubProgram, Milestone, and Activity.
 * Tracks created, updated, and deleted events automatically.
 */
class HierarchyObserver
{
    // Fields to skip when logging diffs (IDs, timestamps, etc.)
    protected array $skipFields = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];

    // Human-friendly field labels
    protected array $fieldLabels = [
        'name'           => 'Nama',
        'description'    => 'Deskripsi',
        'bobot'          => 'Bobot (%)',
        'start_date'     => 'Tanggal Mulai',
        'end_date'       => 'Tanggal Selesai',
        'progress'       => 'Progress (%)',
        'status'         => 'Status',
        'uic'            => 'UIC',
        'pic'            => 'PIC',
        'program_id'     => 'Program',
        'sub_program_id' => 'Sub Program',
        'milestone_id'   => 'Milestone',
    ];

    public function created(Model $model): void
    {
        $this->log($model, 'created',
            'menambahkan ' . $this->label($model) . ': ' . ($model->name ?? '#' . $model->id),
            null,
            $this->cleanData($model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        // Filter out skipped fields
        foreach ($this->skipFields as $f) unset($dirty[$f]);
        if (empty($dirty)) return;

        $old = [];
        $new = [];
        foreach ($dirty as $field => $newVal) {
            $old[$field] = $model->getOriginal($field);
            $new[$field] = $newVal;
        }

        $changedLabels = implode(', ', array_map(
            fn($f) => $this->fieldLabels[$f] ?? $f,
            array_keys($dirty)
        ));

        $this->log($model, 'updated',
            'mengubah ' . $this->label($model) . ': ' . ($model->name ?? '#' . $model->id) . ' [' . $changedLabels . ']',
            $old,
            $new
        );
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted',
            'menghapus ' . $this->label($model) . ': ' . ($model->name ?? '#' . $model->id),
            $this->cleanData($model->getAttributes()),
            null
        );
    }

    protected function log(Model $model, string $action, string $desc, ?array $old, ?array $new): void
    {
        ActivityLog::create([
            'loggable_type' => get_class($model),
            'loggable_id'   => $model->getKey(),
            'action'        => $action,
            'description'   => $desc,
            'old_data'      => $old,
            'new_data'      => $new,
        ]);
    }

    protected function label(Model $model): string
    {
        return match (class_basename($model)) {
            'SubProgram' => 'sub program',
            'Milestone'  => 'milestone',
            'Activity'   => 'activity',
            default      => strtolower(class_basename($model)),
        };
    }

    protected function cleanData(array $attrs): array
    {
        foreach ($this->skipFields as $f) unset($attrs[$f]);
        return $attrs;
    }
}
