<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    protected array $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/zip', 'application/x-rar-compressed',
        'text/plain', 'text/csv',
    ];

    /**
     * Upload a new attachment linked to a polymorphic entity.
     */
    public function store(Request $request)
    {
        $request->validate([
            'attachable_type' => 'required|in:sub_program,milestone,activity',
            'attachable_id'   => 'required|integer',
            'type'            => 'required|in:RAB,Evidence,Paparan,Other',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            'file'            => 'required|file|max:20480', // 20 MB max
        ]);

        $file = $request->file('file');

        // Map short type to full model class
        $modelMap = [
            'sub_program' => \App\Models\SubProgram::class,
            'milestone'   => \App\Models\Milestone::class,
            'activity'    => \App\Models\Activity::class,
        ];

        $attachableType = $modelMap[$request->attachable_type];
        $attachableId   = $request->attachable_id;

        // Verify entity exists
        $entity = $attachableType::findOrFail($attachableId);

        // Store file
        $storedPath = $file->store('attachments/' . $request->attachable_type . '/' . $attachableId, 'local');

        $attachment = Attachment::create([
            'attachable_type'   => $attachableType,
            'attachable_id'     => $attachableId,
            'type'              => $request->type,
            'name'              => $request->name,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $storedPath,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
            'description'       => $request->description,
        ]);

        return redirect()->back()->with('success', 'File "' . $attachment->name . '" berhasil diunggah.');
    }

    /**
     * Stream / download an attachment.
     */
    public function download(Attachment $attachment)
    {
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }

    /**
     * Delete an attachment and its physical file.
     */
    public function destroy(Attachment $attachment)
    {
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
