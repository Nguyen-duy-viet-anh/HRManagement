@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header text-primary py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold">
                    <i class="bi bi-cash-stack me-2"></i>QUẢN LÝ BẢNG LƯƠNG
                </h5>
                @if(isset($month))
                    <span class="badge bg-primary text-white px-3 py-2 fw-bold">
                        Tháng {{ \Carbon\Carbon::parse($month)->format('m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="card-body p-4">
            {{-- FORM TÌM KIẾM --}}
            <form id="form-salary" action="{{ route('salary.index') }}" method="GET" class="row g-3 mb-4 p-3 bg-light rounded-3 shadow-sm align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Chọn Tháng</label>
                    <input type="month" name="month" class="form-control border-0 shadow-sm" value="{{ $month ?? date('Y-m') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Chọn Công ty</label>
                    <select name="company_id" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                        <option value="">-- Tất cả công ty --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (isset($company_id) && $company_id == $company->id) ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn flex-grow-1 btn-primary fw-bold shadow-sm">
                        <i class="bi bi-search me-1"></i> XEM BẢNG LƯƠNG
                    </button>
                    
                    @if(isset($users) && count($users) > 0)
                        <a href="{{ route('salary.export', ['month' => $month ?? date('Y-m'), 'company_id' => $company_id]) }}" 
                           class="btn btn-outline-dark fw-bold shadow-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i> XUẤT EXCEL
                        </a>
                    @endif
                </div>
            </form>

            {{-- BẢNG DỮ LIỆU --}}
            @if(isset($users) && count($users) > 0)
            <div class="table-responsive rounded-3 border">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="text-center py-3">STT</th> 
                            <th class="py-3">Họ và Tên</th>
                            <th class="text-end py-3">Lương Cơ Bản</th>
                            <th class="text-center py-3">Ngày Công</th>
                            <th class="text-end py-3" style="min-width: 150px;">Thực Lĩnh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="text-center text-muted fw-bold">
                                {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->email }}</div>
                            </td>
                            <td class="text-end fw-semibold text-secondary">
                                {{ number_format($user->base_salary) }} <small>đ</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-soft-info text-info rounded-pill px-3">
                                    {{ $user->work_days }} ngày
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success fs-5">
                                    {{ number_format($user->total_salary) }}
                                </span>
                                <small class="text-success">đ</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                
                <div class="d-flex justify-content-center mt-4 pb-3">
                    @if(isset($users) && $users->hasPages())
                        {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                    @endif
                </div>
            </div>
            
            <div class="mt-3 text-muted small italic">
                * Công thức tính: (Lương cơ bản / Ngày công chuẩn của công ty) x Số ngày làm việc thực tế.
            </div>

            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-wallet2 text-light" style="font-size: 4rem;"></i>
                    </div>
                    @if(request('company_id'))
                        <h6 class="text-muted">Chưa có dữ liệu chấm công hoặc lương cho lựa chọn này.</h6>
                    @else
                        <h6 class="text-success fw-bold">Vui lòng chọn Công ty để xem chi tiết bảng lương.</h6>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .bg-dark { background-color: #2d3436 !important; }
    .bg-soft-info { background-color: #e0f7fa; }
    .text-info { color: #00acc1 !important; }
    .table-hover tbody tr:hover { background-color: #f1f8e9; transition: 0.3s; }
    .fs-5 { font-size: 1.1rem !important; }
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        border-color: #198754;
    }
</style>
@endsection