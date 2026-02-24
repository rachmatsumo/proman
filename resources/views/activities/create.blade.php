@extends('layouts.app')

@section('title', 'Create Activity - ProMan')
@section('header_title', 'Create New Activity')

@section('header_actions')
    <a href="{{ route('programs.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-medium px-3">
        &larr; Back to Program List
    </a>
@endsection

@section('content')
<div class="container-xl" style="max-width: 800px;">
    <div class="card shadow-sm border-secondary border-opacity-25">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('activities.store') }}" method="POST" class="d-flex flex-column gap-4">
                @csrf
                <div>
                    <label class="form-label fw-semibold text-dark">Pilih Milestone <span class="text-danger">*</span></label>
                    <select name="milestone_id" class="form-select" required>
                        <option value="">-- Select Milestone --</option>
                        @foreach($milestones as $ms)
                            <option value="{{ $ms->id }}" {{ $selectedMilestone == $ms->id ? 'selected' : '' }}>{{ $ms->subProgram->program->name }} > {{ $ms->subProgram->name }} > {{ $ms->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark">Nama Activity <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div>
                    <label class="form-label fw-semibold text-dark">Deskripsi</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Draft">Draft</option>
                            <option value="To Do">To Do</option>
                            <option value="On Progress">On Progress</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Done">Done</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Progress (%) <span class="text-danger">*</span></label>
                        <input type="number" name="progress" min="0" max="100" value="0" class="form-control" required>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Unit In Charge (UIC)</label>
                        <input type="text" name="uic" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Person In Charge (PIC)</label>
                        <input type="text" name="pic" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="pt-3 border-top mt-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Save Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
