@extends('layouts.app')

@section('title', 'List Programs - ProMan')
@section('header_title', 'Program List')

@section('header_actions')
    <a href="{{ route('programs.create') }}" class="btn btn-primary btn-sm shadow-sm fw-medium">
        <i class="fa-solid fa-plus me-2"></i> New Program
    </a>
@endsection

@section('content')
<div class="card shadow-sm border-secondary border-opacity-25 container-xl px-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary">
                <tr>
                    <th scope="col" class="py-3 px-4 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05rem;">Program Name</th>
                    <th scope="col" class="py-3 px-4 fw-semibold text-uppercase w-25" style="font-size: 0.75rem; letter-spacing: 0.05rem;">Dates</th>
                    <th scope="col" class="py-3 px-4 fw-semibold text-uppercase w-25" style="font-size: 0.75rem; letter-spacing: 0.05rem;">Sub Programs</th>
                    <th scope="col" class="py-3 px-4 fw-semibold text-uppercase text-end w-25" style="font-size: 0.75rem; letter-spacing: 0.05rem;">Actions</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                @forelse($programs as $program)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark d-flex align-items-center">
                                <i class="fa-solid fa-folder text-primary me-3 fs-5"></i> 
                                {{ $program->name }}
                            </div>
                            @if($program->description)
                                <div class="text-muted mt-1 text-truncate" style="font-size: 0.8rem; padding-left: 2.2rem; max-width: 300px;">{{ $program->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-secondary" style="font-size: 0.8rem;">
                            <div><span class="fw-medium text-dark">Start:</span> {{ $program->start_date ? $program->start_date->format('d M Y') : '-' }}</div>
                            <div class="mt-1"><span class="fw-medium text-dark">End:</span> {{ $program->end_date ? $program->end_date->format('d M Y') : '-' }}</div>
                        </td>
                        <td class="px-4 py-3">
                           <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 fw-medium" style="font-size: 0.75rem;">
                               {{ $program->subPrograms->count() }} Sub Programs
                           </span>
                        </td>
                        <td class="px-4 py-3 text-end text-nowrap">
                            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center me-1">
                                <i class="fa-solid fa-eye me-1"></i> Show 
                            </a>
                            
                            <form action="{{ route('programs.destroy', $program->id) }}" method="POST" onsubmit="return confirm('Hapus program ini secara permanen?')" class="d-inline-block">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-5 text-center text-muted">
                            <div class="text-primary opacity-50 mx-auto mb-3">
                                <i class="fa-solid fa-folder-open display-4"></i>
                            </div>
                            <p class="fs-6 fw-medium text-dark mb-1">No Programs Found</p>
                            <p class="small mb-0">Get started by creating a new program.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
