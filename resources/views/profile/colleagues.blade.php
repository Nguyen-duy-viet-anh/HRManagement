@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="m-0 fw-bold text-primary">
                <i class="bi bi-people-fill me-2"></i>DANH SÁCH ĐỒNG NGHIỆP
            </h5>
        </div>

        <div class="card-body">
            <div class="alert alert-soft-primary d-flex align-items-center border-0 shadow-sm mb-4">
                <i class="bi bi-building me-3 fs-4 text-primary"></i>
                <div>
                    Bạn đang xem danh sách nhân sự thuộc công ty: 
                    <strong class="text-primary">{{ Auth::user()->company->name ?? 'N/A' }}</strong>
                </div>
            </div>

            @if(isset($colleagues) && count($colleagues) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 70px;">STT</th>
                            <th style="width: 80px;">Avatar</th>
                            <th>Thông tin đồng nghiệp</th>
                            <th>Email liên hệ</th>
                            <th class="text-center">Vai trò</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($colleagues as $colleague)
                        <tr class="{{ $colleague->id == Auth::id() ? 'table-primary-light' : '' }}">
                            <td class="text-center fw-bold text-muted">
                                {{ ($colleagues->currentPage() - 1) * $colleagues->perPage() + $loop->iteration }}
                            </td>
                            
                            <td class="text-center">
                                @php
                                    $avatarUrl = filter_var($colleague->avatar, FILTER_VALIDATE_URL) 
                                        ? $colleague->avatar 
                                        : ($colleague->avatar ? asset('storage/' . $colleague->avatar) : null);
                                @endphp

                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" width="45" height="45" class="rounded-circle shadow-sm border" style="object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border mx-auto" style="width: 45px; height: 45px;">
                                        <i class="bi bi-person text-secondary fs-4"></i>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center">
                                    {{ $colleague->name }}
                                    
                                    @if($colleague->id == Auth::id())
                                        <span class="badge bg-primary ms-2" style="font-size: 0.65rem;">TÔI</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <a href="mailto:{{ $colleague->email }}" class="text-decoration-none d-flex align-items-center">
                                    <i class="bi bi-envelope-at me-2 text-muted"></i>
                                    {{ $colleague->email }}
                                </a>
                            </td>

                            <td class="text-center">
                                @if($colleague->role == 1)
                                    <span class="badge rounded-pill bg-soft-danger text-danger border border-danger px-3">
                                        Quản lý
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-soft-secondary text-secondary border border-secondary px-3">
                                        Nhân viên
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $colleagues->links('pagination::bootstrap-5') }}
            </div>

            @else
            <div class="text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Bạn chưa có đồng nghiệp nào trong công ty này.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .alert-soft-primary { background-color: #e7f1ff; color: #084298; }
    .bg-soft-danger { background-color: #f8d7da; }
    .bg-soft-secondary { background-color: #f8f9fa; }
    .table-primary-light { background-color: #f0f7ff; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; transition: 0.2s; }
</style>
@endsection