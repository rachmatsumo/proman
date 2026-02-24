@extends('layouts.app')

@section('title', 'Team Members - ProMan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Team Members</h1>
            <p class="text-muted small mb-0">Manage your project team and permissions.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-user-plus me-2"></i> Add Member
        </a>
    </div> 

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="userSearch" class="form-control bg-light border-0" placeholder="Search members...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-muted fw-semibold small text-uppercase" style="letter-spacing: 0.05em;">Member</th>
                        <th class="px-3 py-3 text-muted fw-semibold small text-uppercase" style="letter-spacing: 0.05em;">Contact</th>
                        <th class="px-3 py-3 text-muted fw-semibold small text-uppercase" style="letter-spacing: 0.05em;">Role</th>
                        <th class="px-3 py-3 text-muted fw-semibold small text-uppercase" style="letter-spacing: 0.05em;">Status</th>
                        <th class="px-4 py-3 text-muted fw-semibold small text-uppercase text-end" style="letter-spacing: 0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($users as $user)
                    <tr class="user-row">
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($user->avatar)
                                    <img src="{{ asset($user->avatar) }}" class="rounded-circle shadow-sm" style="width: 42px; height: 42px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 0.9rem; border: 1px solid rgba(79, 70, 229, 0.2);">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark user-name">{{ $user->name }}</div>
                                    <div class="text-muted small">@<span>{{ $user->username ?? 'no-username' }}</span></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="small text-dark mb-1"><i class="fa-regular fa-envelope me-2 opacity-50"></i>{{ $user->email }}</div>
                            @if($user->phone)
                                <div class="small text-muted"><i class="fa-solid fa-phone me-2 opacity-50"></i>{{ $user->phone }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @php
                                $roleClass = 'bg-secondary text-white';
                                if($user->role == 'admin')   $roleClass = 'bg-danger text-white';
                                if($user->role == 'manager') $roleClass = 'bg-info text-dark';
                                if($user->role == 'member')  $roleClass = 'bg-primary text-white';
                            @endphp
                            <span class="badge rounded-pill {{ $roleClass }} px-3 py-1 fw-semibold small" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.03em;">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            @if($user->status == 'active')
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-semibold small" style="font-size: 0.65rem;">Active</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 fw-semibold small" style="font-size: 0.65rem;">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-light border p-2 rounded-3 text-primary" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border p-2 rounded-3 text-danger" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-5 text-center">
                            <div class="opacity-25 mb-3" style="font-size: 3rem; color: #64748b;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <h6 class="text-muted fw-normal">No team members found.</h6>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="card-footer bg-white py-3 px-4">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.getElementById('userSearch')?.addEventListener('input', function(e) {
    let term = e.target.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        let name = row.querySelector('.user-name').textContent.toLowerCase();
        let email = row.textContent.toLowerCase();
        if (name.includes(term) || email.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
@endpush

@endsection
