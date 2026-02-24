@extends('layouts.app')

@section('title', 'Add Team Member - ProMan')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="{{ route('users.index') }}" class="btn btn-light border p-2 rounded-3 text-muted" title="Back">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h4 fw-bold text-dark mb-0">Add Team Member</h1>
                    <p class="text-muted small mb-0">Create a new user account and assign a role.</p>
                </div>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 px-4 py-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                    </div>
                    <div class="fw-semibold">Please fix the following errors:</div>
                </div>
                <ul class="mb-0 mt-2 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-4">
                            <div class="col-md-12 text-center mb-2">
                                <div class="position-relative d-inline-block">
                                    <div id="avatarPreview" class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted shadow-sm" style="width: 120px; height: 120px; border: 2px dashed #cbd5e1; overflow: hidden;">
                                        <i class="fa-solid fa-user fs-1 opacity-25"></i>
                                    </div>
                                    <label for="avatarInput" class="btn btn-sm btn-primary rounded-circle shadow-sm position-absolute bottom-0 end-0 p-2" style="width: 34px; height: 34px; cursor: pointer;">
                                        <i class="fa-solid fa-camera"></i>
                                        <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*">
                                    </label>
                                </div>
                                <p class="text-muted mt-2 small">Profile Photo (Optional)</p>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-user text-muted opacity-50"></i></span>
                                    <input type="text" name="name" class="form-control border-start-0" placeholder="e.g. John Doe" value="{{ old('name') }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-at text-muted opacity-50"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0" placeholder="e.g. johndoe" value="{{ old('username') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-envelope text-muted opacity-50"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0" placeholder="e.g. john@example.com" value="{{ old('email') }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone text-muted opacity-50"></i></span>
                                    <input type="text" name="phone" class="form-control border-start-0" placeholder="e.g. 0812..." value="{{ old('phone') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="member" {{ old('role') == 'member' ? 'selected' : '' }}>Team Member</option>
                                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Project Manager</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted opacity-50"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted opacity-50"></i></span>
                                    <input type="password" name="password_confirmation" class="form-control border-start-0" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Account Status</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusActive">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" {{ old('status') == 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="statusInactive">Inactive</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 pt-3">
                                <hr class="border-secondary opacity-10 mb-4">
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Member
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-light border rounded-pill px-4 text-muted">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
            preview.style.borderStyle = 'solid';
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush

@endsection
