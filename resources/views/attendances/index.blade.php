@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-primary">
                    <i class="bi bi-calendar-check me-2"></i>CHẤM CÔNG HÀNG NGÀY
                </h5>
                <span class="badge bg-soft-primary text-primary px-3 py-2">
                    Ngày: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                </span>
            </div>
        </div>

        <div class="card-body p-4">
            <form id="filter-form" action="{{ route('attendance.index') }}" method="GET" class="row g-2 mb-4 p-3 bg-light rounded-3 align-items-end">
    
    {{-- 1. Ngày --}}
    <div class="col-md-3">
        <label class="fw-bold small text-muted">NGÀY</label>
        <input type="date" name="date" class="form-control" value="{{ $date }}">
    </div>

    {{-- 2. Công ty --}}
    @if(Auth::user()->role == 0)
        <div class="col-md-3"> {{-- Giảm độ rộng để nhường chỗ cho ô Trạng thái --}}
            <label class="fw-bold small text-muted">CÔNG TY</label>
            <select name="company_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Chọn công ty --</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $company_id == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <input type="hidden" name="company_id" value="{{ $company_id }}">
    @endif

    {{-- 3. [MỚI] Trạng thái --}}
    <div class="col-md-2">
        <label class="fw-bold small text-muted">TRẠNG THÁI</label>
        <select name="status" class="form-select">
            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Tất cả</option>
            <option value="1" {{ $status == '1' ? 'selected' : '' }}>✅ Đã chấm</option>
            <option value="0" {{ $status == '0' ? 'selected' : '' }}>❌ Chưa chấm</option>
        </select>
    </div>

    {{-- 4. Tìm Tên --}}
    <div class="col-md-2">
        <label class="fw-bold small text-muted">TÊN NHÂN VIÊN</label>
        <input type="text" name="search_name" class="form-control" 
               placeholder="Nhập tên..." value="{{ $search_name ?? '' }}">
    </div>

    {{-- 5. Nút Tìm --}}
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="bi bi-search"></i> Tìm
        </button>
    </div>
</form>
            @if(isset($users) && count($users) > 0)
            <form action="{{ route('attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <input type="hidden" name="company_id" value="{{ $company_id }}">
                <input type="hidden" name="page" value="{{ request('page', 1) }}">

                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center py-3" style="width: 80px;">ID</th>
                                <th class="py-3">Thông tin Nhân viên</th>
                                <th class="text-center py-3" style="width: 180px;">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td class="text-center text-muted fw-bold">
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input custom-switch" type="checkbox" role="switch"
                                               name="present[{{ $user->id }}]" 
                                               value="1"
                                               {{ $user->is_present ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sticky-bottom bg-white p-3 mt-4 border-top d-flex justify-content-between align-items-center shadow-sm rounded-3">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Hãy bấm lưu trước khi chuyển trang.
                    </div>
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow">
                        LƯU CHẤM CÔNG TRANG NÀY
                    </button>
                </div>

    
                @if(isset($users) && $users->hasPages())
                    <div class="d-flex justify-content-center mt-4 pb-5">
                    @if(isset($users) && $users->hasPages())
                        {{ $users->appends([
                            'date' => $date, 
                            'company_id' => $company_id,
                            'search_name' => $search_name ?? ''
                        ])->links('pagination::bootstrap-5') }}
                    @endif
                </div>
                @endif
            </form>
            @else
                <div class="text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="opacity-25 mb-3">
                    @if($company_id)
                        <h6 class="text-muted">Công ty này chưa có nhân viên nào.</h6>
                    @else
                        <h6 class="text-primary fw-bold">Vui lòng chọn Công ty để bắt đầu chấm công.</h6>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Custom CSS để giao diện mượt hơn */
    .bg-soft-primary { background-color: #e7f1ff; }
    .table thead th { font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase; }
    .custom-switch { width: 3rem !important; height: 1.5rem !important; cursor: pointer; }
    .form-check-input:checked { background-color: #198754; border-color: #198754; }
    .avatar-sm { font-size: 0.9rem; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; transition: 0.2s; }
    .sticky-bottom { z-index: 1020; }
</style>
@endsection