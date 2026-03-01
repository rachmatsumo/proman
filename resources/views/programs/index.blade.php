@extends('layouts.app')

@section('title', 'List Programs - ProMan')
@section('header_title', 'Program List')

@section('header_actions')
    <div class="d-flex align-items-center gap-2 gap-md-3 flex-grow-1 justify-content-end">
        <div class="position-relative flex-grow-1 flex-md-grow-0" style="max-width: 250px;">
            <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size: 0.8rem;"></i>
            <input type="text" id="programSearch" class="form-control form-control-sm ps-5 bg-light border-0 shadow-none rounded-pill" placeholder="Search..." style="height: 36px; border: 1px solid #e2e8f0 !important; font-size: 0.85rem;">
        </div>
        <a href="{{ route('programs.create') }}" class="btn btn-primary btn-sm shadow-sm fw-bold px-3 d-flex align-items-center flex-shrink-0" style="height: 36px; border-radius: 10px;">
            <i class="fa-solid fa-plus me-md-2"></i> <span class="d-none d-md-inline">New Program</span>
        </a>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="programsTable">
            <thead class="bg-light text-secondary">
                <tr>
                    <th scope="col" class="py-4 px-4 fw-bold border-0 text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Program Name</th>
                    <th scope="col" class="py-4 px-4 fw-bold border-0 text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Timeline</th>
                    <th scope="col" class="py-4 px-4 fw-bold border-0 text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Status</th>
                    <th scope="col" class="py-4 px-4 fw-bold border-0 text-uppercase text-muted text-end" style="font-size: 0.7rem; letter-spacing: 0.05rem;">Management</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                @forelse($programs as $program)
                    <tr class="program-row border-bottom border-light">
                        <td class="px-4 py-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3" style="min-width: 44px; min-height: 44px;">
                                    <i class="fa-solid fa-folder text-primary fs-5"></i> 
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark fs-6 program-name">{{ $program->name }}</div>
                                    @if($program->description)
                                        <div class="text-muted mt-1 text-truncate" style="font-size: 0.75rem; max-width: 400px;">{{ $program->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-secondary">
                            <div class="d-flex flex-column gap-1" style="font-size: 0.8rem;">
                                <div class="d-flex align-items-center">
                                    <i class="fa-regular fa-calendar-check me-2 opacity-50" style="width: 14px;"></i>
                                    <span>{{ $program->start_date ? $program->start_date->format('d M Y') : '-' }}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fa-regular fa-calendar-xmark me-2 opacity-50" style="width: 14px;"></i>
                                    <span>{{ $program->end_date ? $program->end_date->format('d M Y') : '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                           <div class="d-flex align-items-center">
                               <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.7rem;">
                                   <i class="fa-solid fa-layer-group me-1"></i>
                                   {{ $program->subPrograms->count() }} Sub Programs
                               </span>
                           </div>
                        </td>
                        <td class="px-4 py-4 text-end text-nowrap">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('programs.show', $program->id) }}" class="btn btn-sm btn-light border d-inline-flex align-items-center fw-bold px-3" style="border-radius: 8px;">
                                    <i class="fa-solid fa-arrow-right-long me-2"></i> Details
                                </a>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border px-2" type="button" data-bs-toggle="dropdown" style="border-radius: 8px;">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li>
                                            <form action="{{ route('programs.destroy', $program->id) }}" method="POST" onsubmit="return confirm('Hapus program ini secara permanen?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center">
                                                    <i class="fa-solid fa-trash-can me-2"></i> Delete Program
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-5 text-center text-muted">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-box-open fs-1 opacity-25"></i>
                            </div>
                            <p class="fs-6 fw-bold text-dark mb-1">No Programs Found</p>
                            <p class="small mb-4 text-muted">Start organizing your projects by creating a new program.</p>
                            <a href="{{ route('programs.create') }}" class="btn btn-primary px-4 fw-bold">
                                <i class="fa-solid fa-plus me-2"></i> Create First Program
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('programSearch').addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.program-row');
        
        rows.forEach(row => {
            const name = row.querySelector('.program-name').textContent.toLowerCase();
            const description = row.querySelector('.text-muted') ? row.querySelector('.text-muted').textContent.toLowerCase() : '';
            
            if (name.includes(query) || description.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Handle empty state if needed (optional)
    });
</script>
@endpush
@endsection
