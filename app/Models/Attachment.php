<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'type',
        'name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'description',
    ];

    protected $appends = ['file_size_human', 'icon_class', 'download_url'];

    /**
     * Polymorphic: belongs to SubProgram, Milestone, or Activity
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    /**
     * FontAwesome icon based on mime type.
     */
    public function getIconClassAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_contains($mime, 'pdf'))        return 'fa-file-pdf text-danger';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'fa-file-word text-primary';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return 'fa-file-excel text-success';
        if (str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')) return 'fa-file-powerpoint text-warning';
        if (str_contains($mime, 'image'))      return 'fa-file-image text-info';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'fa-file-zipper text-secondary';
        return 'fa-file text-muted';
    }

    /**
     * Download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    /**
     * Type color mapping for UI badges.
     */
    public static function typeColor(string $type): array
    {
        return match ($type) {
            'RAB'      => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
            'Evidence' => ['bg' => '#d1fae5', 'color' => '#065f46'],
            'Paparan'  => ['bg' => '#fef3c7', 'color' => '#92400e'],
            default    => ['bg' => '#f1f5f9', 'color' => '#475569'],
        };
    }
}
