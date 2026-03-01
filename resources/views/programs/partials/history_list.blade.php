@if($activityLogs->isEmpty())
    <div class="text-center py-5">
        <i class="fa-solid fa-wind text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
        <p class="text-muted">Tidak ada riwayat aktivitas yang ditemukan.</p>
    </div>
@else
    <div class="position-relative ms-1 ms-md-4">
        {{-- Vertical Line --}}
        <div class="position-absolute top-0 bottom-0" style="left: 15px; width: 2px; background: #e2e8f0; transform: translateX(-50%);"></div>

        <div class="d-flex flex-column gap-4">
            @foreach($activityLogs as $log)
                @php 
                    $color = $log->action_color; 
                    $changed = $log->changed_fields;
                    $user = $log->user;
                @endphp
                <div class="position-relative ps-5 pb-1">
                    {{-- Timeline node --}}
                    <div class="position-absolute rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="left: 15px; top: 0; width: 32px; height: 32px; background: {{ $color['bg'] }}; color: {{ $color['color'] }}; border: 2px solid white; transform: translateX(-50%); z-index: 1;">
                        <i class="fa-solid {{ $color['icon'] }}" style="font-size: 0.8rem;"></i>
                    </div>
                    
                    {{-- Content --}}
                    <div class="card border-0 shadow-sm rounded-4" style="background: #f8fafc; border: 1px solid #f1f5f9 !important;">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge rounded-pill fw-semibold" style="background: {{ $color['bg'] }}; color: {{ $color['color'] }}; font-size: 0.62rem; border: 1px solid {{ $color['color'] }}33;">
                                            {{ strtoupper($log->action) }}
                                        </span>
                                        <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary fw-semibold" style="font-size: 0.62rem;">
                                            {{ strtoupper($log->entity_label) }}
                                        </span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.88rem;">{{ ucfirst($log->description) }}</h6>
                                    @if($user)
                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-user-circle opacity-50"></i>
                                            <span class="fw-semibold">{{ $user->name }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-end" style="min-width: 100px;">
                                    <span class="text-muted fw-semibold d-block" style="font-size: 0.7rem;">{{ $log->created_at->format('d M Y, H:i') }}</span>
                                    <span class="text-muted d-block opacity-75" style="font-size: 0.65rem;">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            @if($log->action === 'updated' && !empty($changed))
                                <div class="mt-3 bg-white rounded-3 border p-3">
                                    <p class="text-muted fw-semibold mb-2" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;">Perubahan Data</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 0.75rem;">
                                            <tbody>
                                                @foreach($changed as $c)
                                                    <tr>
                                                        <td class="fw-semibold text-secondary" style="width: 25%;">{{ ucfirst(str_replace('_', ' ', $c['field'])) }}</td>
                                                        <td class="text-danger opacity-75 text-decoration-line-through text-truncate" style="max-width: 150px;" title="{{ $c['old'] }}">{{ $c['old'] ?? '-' }}</td>
                                                        <td style="width: 20px;" class="text-center text-muted"><i class="fa-solid fa-arrow-right"></i></td>
                                                        <td class="text-success fw-medium text-truncate" style="max-width: 150px;" title="{{ $c['new'] }}">{{ $c['new'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-5 d-flex justify-content-center ajax-pagination">
            {{ $activityLogs->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
