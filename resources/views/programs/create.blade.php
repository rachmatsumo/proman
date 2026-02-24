@extends('layouts.app')

@section('title', 'Create Program - ProMan')
@section('header_title', 'Create New Program')

@section('header_actions')
    <a href="{{ route('programs.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-medium px-3">
        &larr; Back to List
    </a>
@endsection

@section('content')
<div class="container-xl" style="max-width: 800px;">
    <div class="card shadow-sm border-secondary border-opacity-25">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('programs.store') }}" method="POST" class="d-flex flex-column gap-4">
                @csrf
                <div>
                    <label class="form-label fw-semibold text-dark">Nama Program Utama <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Sistem Informasi Terpadu">
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
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Save Program</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
