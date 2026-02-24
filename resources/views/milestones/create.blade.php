@extends('layouts.app')

@section('title', 'Create Milestone - ProMan')
@section('header_title', 'Create New Milestone')

@section('header_actions')
    <a href="{{ route('programs.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-medium px-3">
        &larr; Back to List
    </a>
@endsection

@section('content')
<div class="container-xl" style="max-width: 800px;">
    <div class="card shadow-sm border-secondary border-opacity-25">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('milestones.store') }}" method="POST" class="d-flex flex-column gap-4">
                @csrf
                <div>
                    <label class="form-label fw-semibold text-dark">Pilih Sub Program <span class="text-danger">*</span></label>
                    <select name="sub_program_id" class="form-select" required>
                        <option value="">-- Select Sub Program --</option>
                        @foreach($subPrograms as $sub)
                            <option value="{{ $sub->id }}" {{ $selectedSubProgram == $sub->id ? 'selected' : '' }}>{{ $sub->program->name }} > {{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark">Nama Milestone <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark">Deskripsi</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>
                <div class="pt-3 border-top mt-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Save Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
