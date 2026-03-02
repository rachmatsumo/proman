@if($attachments->isEmpty())
    <div class="text-center py-5 bg-white rounded-4 border w-100">
        <i class="fa-solid fa-folder-open text-muted opacity-25 mb-3" style="font-size: 3.5rem;"></i>
        <p class="text-muted fw-semibold">Tidak ditemukan lampiran yang sesuai dengan filter.</p>
    </div>
@else
    <div class="row g-3 w-100">
        @foreach($attachments as $att)
        @php 
            $tc = \App\Models\Attachment::typeColor($att->type); 
            $entityName = 'Unknown';
            $entityType = '';
            if ($att->attachable_type === 'App\Models\SubProgram' && $att->attachable) {
                $entityName = $att->attachable->name;
                $entityType = 'SUB PROGRAM';
            } elseif ($att->attachable_type === 'App\Models\Milestone' && $att->attachable) {
                $entityName = $att->attachable->name;
                $entityType = 'MILESTONE';
            } elseif ($att->attachable_type === 'App\Models\Activity' && $att->attachable) {
                $entityName = $att->attachable->name;
                $entityType = 'ACTIVITY';
            } elseif ($att->attachable_type === 'App\Models\SubActivity' && $att->attachable) {
                $entityName = $att->attachable->name;
                $entityType = 'SUB ACTIVITY';
            }
        @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift" style="transition: transform 0.2s, box-shadow 0.2s;">
                <div class="card-body p-3 d-flex flex-column">
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <span class="badge rounded-pill px-2 py-1 fw-bold" style="font-size: 0.55rem; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">{{ $entityType }}</span>
                        <span class="text-muted text-truncate fw-medium" style="font-size: 0.65rem;" title="{{ $entityName }}">{{ $entityName }}</span>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #f8fafc; border: 1px solid #e2e8f0;">
                            <i class="fa-solid {{ $att->file_path ? $att->icon_class : 'fa-note-sticky text-warning' }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.85rem;" title="{{ $att->name }}">{{ $att->name }}</h6>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge rounded-pill fw-semibold" style="background: {{ $tc['bg'] }}; color: {{ $tc['color'] }}; font-size: 0.6rem; border: 1px solid {{ $tc['color'] }}33;">
                                    {{ $att->type }}
                                </span>
                                <span class="text-muted" style="font-size: 0.65rem;">{{ $att->file_path ? $att->file_size_human : 'Hanya Catatan' }}</span>
                            </div>
                            <p class="text-muted small mb-0 flex-grow-1 text-truncate" style="font-size: 0.7rem;" title="{{ $att->file_path ? $att->original_filename : 'Catatan Lampiran' }}">{{ $att->file_path ? $att->original_filename : 'Catatan Lampiran' }}</p>
                        </div>
                    </div>
                    @if($att->description)
                        <p class="text-muted fst-italic mb-3 px-2 py-1 rounded-2" style="font-size: 0.7rem; background: #f1f5f9;">{{ Str::limit($att->description, 60) }}</p>
                    @endif
                    <div class="d-flex gap-2 mt-auto pt-2 border-top border-light-subtle">
                        @if($att->file_path)
                        <a href="{{ $att->download_url }}" class="btn btn-sm flex-grow-1 fw-semibold" style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 0.75rem;">
                            <i class="fa-solid fa-download me-1"></i> Download
                        </a>
                        @endif
                        <button type="button" class="btn btn-sm {{ $att->file_path ? 'px-2' : 'flex-grow-1' }}" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.75rem;" title="Hapus"
                                onclick="deleteHierarchyItem('{{ route('attachments.destroy', $att->id) }}', 'Hapus dokumen ini?', () => { refreshPageContent(); fetchAttachments(); })">
                            <i class="fa-solid fa-trash me-1"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center attachment-pagination">
        {{ $attachments->links('pagination::bootstrap-5') }}
    </div>
@endif
