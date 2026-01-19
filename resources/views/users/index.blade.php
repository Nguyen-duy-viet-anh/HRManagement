@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="m-0 fw-bold text-primary">
                <i class="bi bi-people-fill me-2"></i>DANH SÁCH NHÂN SỰ
            </h5>
            <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Thêm nhân viên
            </a>
        </div>
        <div class="card-body bg-light border-bottom">
    <form action="{{ route('users.index') }}" method="GET">
        <div class="row g-2 justify-content-end">
            
            @if(Auth::user()->role == 0)
            <div class="col-md-3">
                <select name="company_id" class="form-select border-primary" onchange="this.form.submit()">
                    <option value="">-- Tất cả công ty --</option>
                    @foreach($companies as $cp)
                        <option value="{{ $cp->id }}" {{ request('company_id') == $cp->id ? 'selected' : '' }}>
                            {{ $cp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" name="search" class="form-control border-primary" 
                           placeholder="Tên hoặc email..." 
                           value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    
                    @if(request('search') || request('company_id'))
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="Xóa tất cả bộ lọc">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
        <div class="card-body p-0"> <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 70px;">STT</th>
                            <th style="width: 80px;">Avatar</th>
                            <th>Thông tin nhân viên</th>
                            <th>Công ty</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="text-center" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="text-center fw-bold text-muted">
                                {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                            </td>
                            <td class="text-center">
                                @php
                                    // Kiểm tra nếu avatar là một URL (từ Faker) hoặc đường dẫn file (từ Storage)
                                    $avatarUrl = filter_var($user->avatar, FILTER_VALIDATE_URL) 
                                        ? $user->avatar 
                                        : ($user->avatar ? asset('storage/' . $user->avatar) : null);
                                @endphp

                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" width="48" height="48" class="rounded-circle shadow-sm border" style="object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-soft-secondary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                                        <i class="bi bi-person text-secondary fs-4"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->email }}</div>
                            </td>
                            <td>
                                @if($user->company)
                                    <span class="text-dark">{{ $user->company->name }}</span>
                                @else
                                    <span class="text-muted italic">Tự do</span>
                                @endif
                            </td>
                            <td>
                                @if($user->role == 0) 
                                    <span class="badge rounded-pill bg-danger px-3">Super Admin</span>
                                @elseif($user->role == 1) 
                                    <span class="badge rounded-pill bg-warning text-dark px-3">Quản lý</span>
                                @else 
                                    <span class="badge rounded-pill bg-info text-white px-3">Nhân viên</span>
                                @endif
                            </td>
                            <td>
                                @if($user->status == 1) 
                                    <span class="badge bg-soft-success text-success border border-success px-2">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i> Hoạt động
                                    </span>
                                @else 
                                    <span class="badge bg-soft-secondary text-secondary border border-secondary px-2">
                                        Đã nghỉ
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-warning" title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');" class="d-inline">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-center">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<style>
    .bg-soft-success { background-color: #e8f5e9; }
    .bg-soft-secondary { background-color: #f8f9fa; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
    .btn-group .btn { padding: 0.25rem 0.6rem; }
    .badge { font-weight: 500; font-size: 0.75rem; }
</style>
@endsection